package com.icodeap.ecommerce.backend.infrastructure.service;

import com.icodeap.ecommerce.backend.infrastructure.entity.PaymentEntity;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.mail.SimpleMailMessage;
import org.springframework.mail.javamail.JavaMailSender;
import org.springframework.stereotype.Service;

/**
 * Envía un aviso por cada compra: al correo de Isablue (administrador) y al
 * correo del comprador. Si el SMTP no está configurado, no hace nada
 * (no rompe la venta).
 */
@Service
public class MailService {

    @Value("${spring.mail.host:}")
    private String host;
    @Value("${spring.mail.username:}")
    private String from;
    @Value("${mail.admin.to:}")
    private String adminTo;

    @Autowired(required = false)
    private JavaMailSender mailSender;

    public boolean isConfigured() {
        return host != null && !host.isBlank() && mailSender != null;
    }

    /** Avisa al administrador y al comprador de una nueva compra. */
    public void sendAdminCopy(PaymentEntity p) {
        if (!isConfigured()) {
            return;
        }
        String boleta = (p.getBoletaSerie() != null && !p.getBoletaSerie().isBlank())
                ? p.getBoletaSerie() + "-" + p.getBoletaNumber() : "(pendiente)";
        boolean yape = "yape".equalsIgnoreCase(p.getMethod());

        // --- Correo al administrador (Isablue) ---
        if (adminTo != null && !adminTo.isBlank()) {
            StringBuilder b = new StringBuilder();
            b.append("Nueva compra en la tienda Isablue.\n\n");
            b.append("Pedido: #").append(p.getOrderId()).append("\n");
            b.append("Cliente: ").append(p.getCustomerName()).append(" (").append(p.getCustomerEmail()).append(")\n");
            b.append("Monto: S/ ").append(p.getAmount()).append("\n");
            b.append("Método: ").append(p.getMethod()).append("\n");
            if (yape) {
                b.append("N° de operación Yape: ").append(p.getReference()).append("\n");
                b.append("Estado: POR CONFIRMAR — verifica el Yape en tu app.\n");
            } else {
                b.append("Referencia: ").append(p.getReference()).append("\n");
            }
            b.append("Boleta: ").append(boleta).append("\n");
            b.append("\nRevisa el detalle en el panel de administración (Pedidos y pagos).");
            send(adminTo, "Nueva compra Isablue · Pedido #" + p.getOrderId(), b.toString());
        }

        // --- Correo al comprador ---
        if (p.getCustomerEmail() != null && !p.getCustomerEmail().isBlank()) {
            StringBuilder c = new StringBuilder();
            c.append("¡Hola ").append(p.getCustomerName()).append("! 🧸\n\n");
            c.append("Recibimos tu pedido #").append(p.getOrderId())
                    .append(" por S/ ").append(p.getAmount()).append(".\n\n");
            if (yape) {
                c.append("Registramos tu pago por Yape (N° de operación ").append(p.getReference()).append(").\n");
                c.append("Estamos verificando tu pago y te contactaremos por WhatsApp para coordinar la entrega.\n");
            } else {
                c.append("Tu pago fue procesado correctamente. Boleta: ").append(boleta).append(".\n");
            }
            c.append("\nGracias por comprar en Isablue · Juguetes y Accesorios.\nLima, Perú · WhatsApp 920097746");
            send(p.getCustomerEmail(), "Tu pedido en Isablue · #" + p.getOrderId(), c.toString());
        }
    }

    private void send(String to, String subject, String body) {
        try {
            SimpleMailMessage msg = new SimpleMailMessage();
            if (from != null && !from.isBlank()) { msg.setFrom(from); }
            msg.setTo(to);
            msg.setSubject(subject);
            msg.setText(body);
            mailSender.send(msg);
        } catch (Exception ignored) {
            // Un fallo de correo no debe afectar la venta.
        }
    }
}
