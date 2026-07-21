package com.icodeap.ecommerce.backend.infrastructure.rest;

import com.icodeap.ecommerce.backend.application.OrderService;
import com.icodeap.ecommerce.backend.application.UserService;
import com.icodeap.ecommerce.backend.domain.model.Order;
import com.icodeap.ecommerce.backend.domain.model.User;
import com.icodeap.ecommerce.backend.infrastructure.adapter.IPaymentCrudRepository;
import com.icodeap.ecommerce.backend.infrastructure.entity.PaymentEntity;
import com.icodeap.ecommerce.backend.infrastructure.exception.BusinessException;
import com.icodeap.ecommerce.backend.infrastructure.service.MailService;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.security.core.Authentication;
import org.springframework.web.bind.annotation.*;

import java.math.BigDecimal;
import java.util.Map;

/**
 * Pago por Yape (confirmación manual). El cliente yapea al número de Isablue,
 * registra su número de operación y el pedido queda "por confirmar" en el panel.
 */
@RestController
@RequestMapping("/api/v1/payments/yape")
public class YapeController {

    @Value("${yape.number:985436488}")
    private String yapeNumber;
    @Value("${yape.name:Haydee Hospinal}")
    private String yapeName;

    private final OrderService orderService;
    private final UserService userService;
    private final MailService mailService;
    private final IPaymentCrudRepository paymentRepository;

    public YapeController(OrderService orderService, UserService userService, MailService mailService,
                          IPaymentCrudRepository paymentRepository) {
        this.orderService = orderService;
        this.userService = userService;
        this.mailService = mailService;
        this.paymentRepository = paymentRepository;
    }

    /** Datos para mostrar en el checkout: número y nombre de Yape. */
    @GetMapping("/info")
    public Map<String, String> info() {
        return Map.of("number", yapeNumber, "name", yapeName);
    }

    /** Diagnóstico temporal del correo. */
    @GetMapping("/mailtest")
    public Map<String, Object> mailtest(@RequestParam(value = "to", required = false) String to) {
        return mailService.diagnose(to);
    }

    @PostMapping("/register")
    public PaymentEntity register(@RequestBody YapeRequest request, Authentication authentication) {
        Order order = orderService.findById(request.orderId());
        User user = userService.findByEmail(authentication.getName());

        if (order.getUserId() == null || !order.getUserId().equals(user.getId())) {
            throw new BusinessException("La orden no pertenece al usuario autenticado");
        }

        BigDecimal total = order.getTotalOrderPrice();
        String operation = (request.operationNumber() == null || request.operationNumber().isBlank())
                ? "YAPE-" + System.currentTimeMillis()
                : request.operationNumber().trim();

        PaymentEntity payment = new PaymentEntity();
        payment.setOrderId(order.getId());
        payment.setCustomerName(user.getFirstName() + " " + user.getLastName());
        payment.setCustomerEmail(user.getEmail());
        payment.setAmount(total);
        payment.setCurrency("PEN");
        payment.setStatus("POR_CONFIRMAR");
        payment.setMethod("yape");
        payment.setReference(operation);
        payment.setBoletaSerie("");
        payment.setBoletaNumber("");
        payment.setBoletaUrl("");

        PaymentEntity saved = paymentRepository.save(payment);
        mailService.sendAdminCopy(saved); // aviso al correo del admin (si SMTP está configurado)
        return saved;
    }

    public record YapeRequest(Integer orderId, String operationNumber) {}
}
