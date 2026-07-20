package com.icodeap.ecommerce.backend.infrastructure.service;

import com.icodeap.ecommerce.backend.application.ProductService;
import com.icodeap.ecommerce.backend.domain.model.Order;
import com.icodeap.ecommerce.backend.domain.model.OrderProduct;
import com.icodeap.ecommerce.backend.domain.model.User;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.*;
import org.springframework.stereotype.Service;
import org.springframework.web.client.RestTemplate;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.util.*;

/**
 * Emisión de boleta electrónica ante SUNAT a través de Nubefact (OSE autorizado).
 * Si no hay credenciales configuradas, emite una boleta interna (serie/número)
 * para poder demostrar el flujo; con credenciales reales, envía a SUNAT y
 * devuelve el enlace al PDF oficial.
 */
@Service
public class NubefactService {

    @Value("${nubefact.url:}")
    private String url;
    @Value("${nubefact.token:}")
    private String token;

    private final RestTemplate rest = new RestTemplate();

    public boolean isConfigured() {
        return url != null && !url.isBlank() && token != null && !token.isBlank();
    }

    /** @return mapa con serie, numero y url (enlace del PDF, vacío en modo interno) */
    public Map<String, String> emitBoleta(User user, Order order, ProductService products, long correlativo) {
        Map<String, String> result = new HashMap<>();
        String serie = "B001";
        String numero = String.valueOf(correlativo);

        if (!isConfigured()) {
            result.put("serie", serie);
            result.put("numero", numero);
            result.put("url", "");
            return result;
        }

        try {
            BigDecimal total = order.getTotalOrderPrice();
            List<Map<String, Object>> items = new ArrayList<>();
            for (OrderProduct op : order.getOrderProducts()) {
                String name = products.findById(op.getProductId()).getName();
                Map<String, Object> item = new LinkedHashMap<>();
                item.put("unidad_de_medida", "NIU");
                item.put("codigo", "P" + op.getProductId());
                item.put("descripcion", name);
                item.put("cantidad", op.getQuantity());
                item.put("valor_unitario", op.getPrice());
                item.put("precio_unitario", op.getPrice());
                item.put("subtotal", op.getTotalItem());
                item.put("tipo_de_igv", 8); // exonerado (demo). Ajustable según régimen del cliente.
                item.put("igv", 0);
                item.put("total", op.getTotalItem());
                items.add(item);
            }

            Map<String, Object> body = new LinkedHashMap<>();
            body.put("operacion", "generar_comprobante");
            body.put("tipo_de_comprobante", 2); // 2 = boleta
            body.put("serie", serie);
            body.put("numero", correlativo);
            body.put("sunat_transaction", 1);
            body.put("cliente_tipo_de_documento", "1"); // DNI
            body.put("cliente_numero_de_documento", "00000000");
            body.put("cliente_denominacion", user.getFirstName() + " " + user.getLastName());
            body.put("cliente_direccion", "Lima, Perú");
            body.put("cliente_email", user.getEmail());
            body.put("fecha_de_emision", LocalDate.now().toString());
            body.put("moneda", 1); // 1 = soles
            body.put("porcentaje_de_igv", 0);
            body.put("total_gravada", 0);
            body.put("total_exonerada", total);
            body.put("total_igv", 0);
            body.put("total", total);
            body.put("enviar_automaticamente_a_la_sunat", true);
            body.put("enviar_automaticamente_al_cliente", false);
            body.put("items", items);

            HttpHeaders headers = new HttpHeaders();
            headers.setContentType(MediaType.APPLICATION_JSON);
            headers.set("Authorization", "Token token=\"" + token + "\"");

            @SuppressWarnings("rawtypes")
            ResponseEntity<Map> response = rest.postForEntity(url, new HttpEntity<>(body, headers), Map.class);
            Map respBody = response.getBody();
            if (respBody != null) {
                Object s = respBody.get("serie");
                Object n = respBody.get("numero");
                Object link = respBody.get("enlace_del_pdf");
                if (s != null) serie = s.toString();
                if (n != null) numero = n.toString();
                if (link != null) result.put("url", link.toString());
            }
        } catch (Exception ex) {
            // La venta ya está pagada: si SUNAT/Nubefact falla, se conserva la boleta interna.
            result.put("url", "");
        }

        result.put("serie", serie);
        result.put("numero", numero);
        result.putIfAbsent("url", "");
        return result;
    }
}
