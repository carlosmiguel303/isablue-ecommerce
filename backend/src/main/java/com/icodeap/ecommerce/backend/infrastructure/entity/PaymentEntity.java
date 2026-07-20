package com.icodeap.ecommerce.backend.infrastructure.entity;

import jakarta.persistence.*;
import lombok.Data;
import org.hibernate.annotations.CreationTimestamp;

import java.math.BigDecimal;
import java.time.LocalDateTime;

/**
 * Registro de un pago realizado sobre una orden.
 * Tabla independiente para no acoplar el flujo de pago/boleta con los mappers de la orden.
 */
@Entity
@Table(name = "payments")
@Data
public class PaymentEntity {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer id;

    private Integer orderId;
    private String customerName;
    private String customerEmail;
    private BigDecimal amount;
    private String currency;
    private String status;      // PAID / FAILED
    private String method;      // culqi / simulado
    private String reference;   // id del cargo en la pasarela

    private String boletaSerie;
    private String boletaNumber;
    @Column(length = 1000)
    private String boletaUrl;

    @CreationTimestamp
    private LocalDateTime createdAt;
}
