package com.icodeap.ecommerce.backend.infrastructure.rest;

import com.icodeap.ecommerce.backend.application.OrderService;
import com.icodeap.ecommerce.backend.application.ProductService;
import com.icodeap.ecommerce.backend.application.UserService;
import com.icodeap.ecommerce.backend.domain.model.Order;
import com.icodeap.ecommerce.backend.domain.model.OrderState;
import com.icodeap.ecommerce.backend.domain.model.User;
import com.icodeap.ecommerce.backend.infrastructure.adapter.IPaymentCrudRepository;
import com.icodeap.ecommerce.backend.infrastructure.entity.PaymentEntity;
import com.icodeap.ecommerce.backend.infrastructure.exception.BusinessException;
import com.icodeap.ecommerce.backend.infrastructure.service.CulqiService;
import com.icodeap.ecommerce.backend.infrastructure.service.MailService;
import com.icodeap.ecommerce.backend.infrastructure.service.NubefactService;
import org.springframework.security.core.Authentication;
import org.springframework.web.bind.annotation.*;

import java.math.BigDecimal;
import java.util.Map;

/**
 * Cobro con tarjeta (Culqi) y emisión de boleta (Nubefact) al confirmarse el pago.
 */
@RestController
@RequestMapping("/api/v1/payments/culqi")
public class CheckoutController {

    private final OrderService orderService;
    private final UserService userService;
    private final ProductService productService;
    private final CulqiService culqiService;
    private final NubefactService nubefactService;
    private final MailService mailService;
    private final IPaymentCrudRepository paymentRepository;
    private final boolean onlinePaymentsEnabled;

    public CheckoutController(OrderService orderService, UserService userService, ProductService productService,
                              CulqiService culqiService, NubefactService nubefactService, MailService mailService,
                              IPaymentCrudRepository paymentRepository,
                              @org.springframework.beans.factory.annotation.Value("${payments.online.enabled:false}") boolean onlinePaymentsEnabled) {
        this.orderService = orderService;
        this.userService = userService;
        this.productService = productService;
        this.culqiService = culqiService;
        this.nubefactService = nubefactService;
        this.mailService = mailService;
        this.paymentRepository = paymentRepository;
        this.onlinePaymentsEnabled = onlinePaymentsEnabled;
    }

    /** Indica al frontend si hay llaves reales o estamos en modo prueba, y la llave pública de Culqi. */
    @GetMapping("/config")
    public Map<String, Object> config() {
        return Map.of(
                "onlineEnabled", onlinePaymentsEnabled,
                "culqiConfigured", culqiService.isConfigured(),
                "boletaConfigured", nubefactService.isConfigured(),
                "publicKey", culqiService.getPublicKey()
        );
    }

    @PostMapping("/charge")
    public PaymentEntity charge(@RequestBody ChargeRequest request, Authentication authentication) {
        if (!onlinePaymentsEnabled) {
            throw new BusinessException("Los pagos en línea están desactivados. Usa Yape/WhatsApp para completar tu compra.");
        }
        Order order = orderService.findById(request.orderId());
        User user = userService.findByEmail(authentication.getName());

        if (order.getUserId() == null || !order.getUserId().equals(user.getId())) {
            throw new BusinessException("La orden no pertenece al usuario autenticado");
        }
        if (order.getOrderState() != OrderState.PENDING) {
            throw new BusinessException("La orden ya fue procesada o cancelada");
        }

        BigDecimal total = order.getTotalOrderPrice();

        String reference;
        try {
            reference = culqiService.charge(total, "PEN", user.getEmail(), request.token());
        } catch (Exception ex) {
            throw new BusinessException("El pago fue rechazado por la pasarela. Verifica los datos de tu tarjeta.");
        }

        orderService.updateStateById(order.getId(), OrderState.CONFIRMED.name());

        long correlativo = paymentRepository.count() + 1;
        Map<String, String> boleta = nubefactService.emitBoleta(user, order, productService, correlativo);

        PaymentEntity payment = new PaymentEntity();
        payment.setOrderId(order.getId());
        payment.setCustomerName(user.getFirstName() + " " + user.getLastName());
        payment.setCustomerEmail(user.getEmail());
        payment.setAmount(total);
        payment.setCurrency("PEN");
        payment.setStatus("PAID");
        payment.setMethod(reference.startsWith("SIM") ? "simulado" : "culqi");
        payment.setReference(reference);
        payment.setBoletaSerie(boleta.get("serie"));
        payment.setBoletaNumber(boleta.get("numero"));
        payment.setBoletaUrl(boleta.get("url"));

        PaymentEntity saved = paymentRepository.save(payment);
        mailService.sendAdminCopy(saved); // copia al correo del admin (si SMTP está configurado)
        return saved;
    }

    public record ChargeRequest(Integer orderId, String token) {}
}
