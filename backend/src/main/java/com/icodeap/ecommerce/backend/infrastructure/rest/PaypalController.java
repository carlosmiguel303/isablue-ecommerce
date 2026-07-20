package com.icodeap.ecommerce.backend.infrastructure.rest;

import com.icodeap.ecommerce.backend.application.OrderService;
import com.icodeap.ecommerce.backend.application.UserService;
import com.icodeap.ecommerce.backend.domain.model.DataPayment;
import com.icodeap.ecommerce.backend.domain.model.Order;
import com.icodeap.ecommerce.backend.domain.model.OrderState;
import com.icodeap.ecommerce.backend.domain.model.URLPaypalResponse;
import com.icodeap.ecommerce.backend.domain.model.User;
import com.icodeap.ecommerce.backend.infrastructure.exception.BusinessException;
import com.icodeap.ecommerce.backend.infrastructure.service.PaypalService;
import com.paypal.api.payments.Links;
import com.paypal.api.payments.Payment;
import com.paypal.base.rest.PayPalRESTException;
import jakarta.validation.Valid;
import lombok.extern.slf4j.Slf4j;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.security.core.Authentication;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.servlet.view.RedirectView;

@RestController
@Slf4j
@RequestMapping("/api/v1/payments")
public class PaypalController {
    private final PaypalService paypalService;
    private final OrderService orderService;
    private final UserService userService;
    private final String frontendUrl;
    private final String backendBaseUrl;
    private final String paymentCurrency;

    public PaypalController(
            PaypalService paypalService,
            OrderService orderService,
            UserService userService,
            @Value("${app.frontend.url:http://localhost:4200}") String frontendUrl,
            @Value("${app.backend.base-url:http://localhost:8085}") String backendBaseUrl,
            @Value("${app.payment.currency:USD}") String paymentCurrency
    ) {
        this.paypalService = paypalService;
        this.orderService = orderService;
        this.userService = userService;
        this.frontendUrl = stripTrailingSlash(frontendUrl);
        this.backendBaseUrl = stripTrailingSlash(backendBaseUrl);
        this.paymentCurrency = paymentCurrency;
    }

    @PostMapping
    public URLPaypalResponse createPayment(
            @Valid @RequestBody DataPayment dataPayment,
            Authentication authentication
    ) {
        Order order = orderService.findById(dataPayment.getOrderId());
        User currentUser = userService.findByEmail(authentication.getName());

        if (!order.getUserId().equals(currentUser.getId())) {
            throw new BusinessException("La orden no pertenece al usuario autenticado");
        }
        if (order.getOrderState() != OrderState.PENDING) {
            throw new BusinessException("La orden ya fue procesada o cancelada");
        }

        String successUrl = backendBaseUrl + "/api/v1/payments/success?orderId=" + order.getId();
        String cancelUrl = backendBaseUrl + "/api/v1/payments/cancel?orderId=" + order.getId();

        try {
            Payment payment = paypalService.createPayment(
                    order.getTotalOrderPrice().doubleValue(),
                    paymentCurrency,
                    "paypal",
                    "sale",
                    "Compra segura IsaBlue - orden " + order.getId(),
                    cancelUrl,
                    successUrl
            );

            return payment.getLinks().stream()
                    .filter(link -> "approval_url".equals(link.getRel()))
                    .map(Links::getHref)
                    .map(URLPaypalResponse::new)
                    .findFirst()
                    .orElseThrow(() -> new BusinessException("PayPal no devolvió una URL de aprobación"));
        } catch (PayPalRESTException ex) {
            log.error("Error creando pago PayPal", ex);
            throw new BusinessException("No fue posible iniciar el pago con PayPal");
        }
    }

    @GetMapping("/success")
    public RedirectView paymentSuccess(
            @RequestParam Integer orderId,
            @RequestParam("paymentId") String paymentId,
            @RequestParam("PayerID") String payerId
    ) {
        try {
            Payment payment = paypalService.executePayment(paymentId, payerId);
            if ("approved".equalsIgnoreCase(payment.getState())) {
                orderService.updateStateById(orderId, OrderState.CONFIRMED.name());
                return new RedirectView(frontendUrl + "/payment/success");
            }
        } catch (PayPalRESTException ex) {
            log.error("Error ejecutando pago PayPal", ex);
        }
        return new RedirectView(frontendUrl + "/cart/sumary?payment=error");
    }

    @GetMapping("/cancel")
    public RedirectView paymentCancel(@RequestParam Integer orderId) {
        orderService.updateStateById(orderId, OrderState.CANCELLED.name());
        return new RedirectView(frontendUrl + "/cart/sumary?payment=cancelled");
    }

    private String stripTrailingSlash(String value) {
        return value.endsWith("/") ? value.substring(0, value.length() - 1) : value;
    }
}
