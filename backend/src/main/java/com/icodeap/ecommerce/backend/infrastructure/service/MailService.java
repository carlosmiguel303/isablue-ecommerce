package com.icodeap.ecommerce.backend.infrastructure.service;

import com.icodeap.ecommerce.backend.infrastructure.entity.PaymentEntity;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.*;
import org.springframework.mail.SimpleMailMessage;
import org.springframework.mail.javamail.JavaMailSender;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestTemplate;

import java.util.*;
import java.util.concurrent.CompletableFuture;

/**
 * Envía un aviso por cada compra al administrador y al comprador.
 * Preferencia: API de Brevo (HTTP, funciona en Railway). Si no hay llave de Brevo,
 * intenta SMTP (que muchos hostings bloquean). Si nada está configurado, no hace nada.
 */
@Service
public class MailService {

    @Value("${brevo.api-key:}")
    private String brevoApiKey;
    @Value("${mail.from.name:Isablue Juguetes}")
    private String fromName;
    @Value("${store.whatsapp:}")
    private String storeWhatsapp;

    @Value("${spring.mail.host:}")
    private String host;
    @Value("${spring.mail.username:}")
    private String from;
    @Value("${mail.admin.to:}")
    private String adminTo;

    @Autowired(required = false)
    private JavaMailSender mailSender;

    private final RestTemplate rest = new RestTemplate();

    private boolean brevoOn() { return brevoApiKey != null && !brevoApiKey.isBlank(); }
    private boolean smtpOn() { return host != null && !host.isBlank() && mailSender != null; }

    public boolean isConfigured() { return brevoOn() || smtpOn(); }

    private String senderEmail() { return (from != null && !from.isBlank()) ? from : adminTo; }

    /** Avisa al admin y al comprador. NO bloquea la venta: envía en segundo plano. */
    public void sendAdminCopy(PaymentEntity p) {
        if (!isConfigured()) { return; }
        CompletableFuture.runAsync(() -> doSend(p));
    }

    private void doSend(PaymentEntity p) {
        String boleta = (p.getBoletaSerie() != null && !p.getBoletaSerie().isBlank())
                ? p.getBoletaSerie() + "-" + p.getBoletaNumber() : "(pendiente)";
        boolean yape = "yape".equalsIgnoreCase(p.getMethod());

        if (adminTo != null && !adminTo.isBlank()) {
            StringBuilder b = new StringBuilder();
            b.append("Nueva compra en la tienda Isablue.\n\n");
            b.append("Pedido: #").append(p.getOrderId()).append("\n");
            b.append("Cliente: ").append(p.getCustomerName()).append(" (").append(p.getCustomerEmail()).append(")\n");
            b.append("Monto: S/ ").append(p.getAmount()).append("\n");
            b.append("Metodo: ").append(p.getMethod()).append("\n");
            if (yape) {
                b.append("N. de operacion Yape: ").append(p.getReference()).append("\n");
                b.append("Estado: POR CONFIRMAR — verifica el Yape en tu app.\n");
            } else {
                b.append("Referencia: ").append(p.getReference()).append("\n");
            }
            b.append("Boleta: ").append(boleta).append("\n");
            b.append("\nRevisa el detalle en el panel de administracion (Pedidos y pagos).");
            send(adminTo, "Nueva compra Isablue - Pedido #" + p.getOrderId(), b.toString());
        }

        if (p.getCustomerEmail() != null && !p.getCustomerEmail().isBlank()) {
            StringBuilder c = new StringBuilder();
            c.append("Hola ").append(p.getCustomerName()).append("!\n\n");
            c.append("Recibimos tu pedido #").append(p.getOrderId())
                    .append(" por S/ ").append(p.getAmount()).append(".\n\n");
            if (yape) {
                c.append("Registramos tu pago por Yape (N. de operacion ").append(p.getReference()).append(").\n");
                c.append("Estamos verificando tu pago y te contactaremos por WhatsApp para coordinar la entrega.\n");
            } else {
                c.append("Tu pago fue procesado correctamente. Boleta: ").append(boleta).append(".\n");
            }
            c.append("\nGracias por comprar en ").append(fromName).append(".");
            if (storeWhatsapp != null && !storeWhatsapp.isBlank()) {
                c.append("\nWhatsApp ").append(storeWhatsapp);
            }
            send(p.getCustomerEmail(), "Tu pedido en Isablue - #" + p.getOrderId(), c.toString());
        }
    }

    private void send(String to, String subject, String body) {
        try {
            if (brevoOn()) { sendViaBrevo(to, subject, body); }
            else if (smtpOn()) { sendViaSmtp(to, subject, body); }
        } catch (Exception ignored) {
            // Un fallo de correo no debe afectar la venta.
        }
    }

    private void sendViaBrevo(String to, String subject, String body) {
        HttpHeaders headers = new HttpHeaders();
        headers.set("api-key", brevoApiKey);
        headers.setContentType(MediaType.APPLICATION_JSON);
        headers.setAccept(List.of(MediaType.APPLICATION_JSON));
        Map<String, Object> payload = new LinkedHashMap<>();
        payload.put("sender", Map.of("name", fromName, "email", senderEmail()));
        payload.put("to", List.of(Map.of("email", to)));
        payload.put("subject", subject);
        payload.put("textContent", body);
        rest.postForEntity("https://api.brevo.com/v3/smtp/email", new HttpEntity<>(payload, headers), String.class);
    }

    private void sendViaSmtp(String to, String subject, String body) {
        SimpleMailMessage msg = new SimpleMailMessage();
        if (from != null && !from.isBlank()) { msg.setFrom(from); }
        msg.setTo(to);
        msg.setSubject(subject);
        msg.setText(body);
        mailSender.send(msg);
    }
}
