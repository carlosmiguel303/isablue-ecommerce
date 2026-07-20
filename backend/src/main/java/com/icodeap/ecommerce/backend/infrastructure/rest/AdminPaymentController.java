package com.icodeap.ecommerce.backend.infrastructure.rest;

import com.icodeap.ecommerce.backend.infrastructure.adapter.IPaymentCrudRepository;
import com.icodeap.ecommerce.backend.infrastructure.entity.PaymentEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.List;

/**
 * Pagos vistos por el administrador de Isablue (quién pagó, cuánto y su boleta).
 * Protegido por seguridad: /api/v1/admin/** requiere rol ADMIN.
 */
@RestController
@RequestMapping("/api/v1/admin/payments")
public class AdminPaymentController {

    private final IPaymentCrudRepository paymentRepository;

    public AdminPaymentController(IPaymentCrudRepository paymentRepository) {
        this.paymentRepository = paymentRepository;
    }

    @GetMapping
    public List<PaymentEntity> list() {
        return paymentRepository.findAllByOrderByIdDesc();
    }
}
