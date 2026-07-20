package com.icodeap.ecommerce.backend.infrastructure.service;

import com.icodeap.ecommerce.backend.infrastructure.entity.PaymentEntity;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.mail.SimpleMailMessage;
import org.springframework.mail.javamail.JavaMailSender;
import org.springframework.stereotype.Service;

/**
 * Envía una copia de cada venta/boleta al correo del administrador de Isablue.
 * Si el SMTP no está configurado, no hace nada (no rompe la venta).
 */
@Service
public class MailService {

    @Value("${spring.mail.host:}")
    private String host;
    @Value("${mail.admin.to:}")
    private String adminTo;

    @Autowired(required = false)
    private JavaMailSender mailSender;

    public boolean isConfigured() {
        return host != null && !host.isBlank() && adminTo != null && !adminTo.isBlank() && mailSender != null;
    }

    public void sendAdminCopy(PaymentEntity p) {
        if (!isConfigured()) {
            return;
        }
        try {
            SimpleMailMessage msg = new SimpleMailMessage();
            msg.setTo(adminTo);
            msg.setSubject("Nueva venta Isablue · Boleta " + p.getBoletaSerie() + "-" + p.getBoletaNumber());
            StringBuilder body = new StringBuilder();
            body.append("Se registró un nuevo pago en la tienda Isablue.\n\n");
            body.append("Boleta: ").append(p.getBoletaSerie()).append("-").append(p.getBoletaNumber()).append("\n");
            body.append("Pedido: #").append(p.getOrderId()).append("\n");
            body.append("Cliente: ").append(p.getCustomerName()).append(" (").append(p.getCustomerEmail()).append(")\n");
            body.append("Monto: S/ ").append(p.getAmount()).append("\n");
            body.append("Método: ").append(p.getMethod()).append("\n");
            body.append("Referencia: ").append(p.getReference()).append("\n");
            if (p.getBoletaUrl() != null && !p.getBoletaUrl().isBlank()) {
                body.append("PDF SUNAT: ").append(p.getBoletaUrl()).append("\n");
            }
            body.append("\nRevisa el detalle en el panel de administración (Pedidos).");
            msg.setText(body.toString());
            mailSender.send(msg);
        } catch (Exception ex) {
            // La venta ya está pagada: un fallo de correo no debe afectarla.
        }
    }
}
