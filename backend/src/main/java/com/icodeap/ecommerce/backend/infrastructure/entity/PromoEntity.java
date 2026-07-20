package com.icodeap.ecommerce.backend.infrastructure.entity;

import jakarta.persistence.*;
import lombok.Data;

import java.util.List;

/**
 * Promoción del banner superior de la tienda. Se guarda en la base de datos
 * para que todas las visitas (no solo el navegador del admin) la vean.
 */
@Entity
@Table(name = "promos")
@Data
public class PromoEntity {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer id;

    private String title;
    private String subtitle;
    private String cta;
    private String link;
    @Column(columnDefinition = "TEXT")
    private String image;   // URL o imagen embebida (dataURL)
    private String bg;      // fondo (gradiente CSS)
    @Column(name = "text_color")
    private String text;    // color del texto

    public static List<PromoEntity> defaults() {
        return List.of(
                build("Envío gratis desde S/ 150", "En todos tus juguetes favoritos, a todo el Perú.",
                        "Ver catálogo", "/tienda", "assets/images/oso.png",
                        "linear-gradient(120deg,#c87a5b 0%,#d99a7c 100%)", "#fff6ef"),
                build("Nueva colección educativa", "Juguetes que acompañan cada etapa del aprendizaje.",
                        "Descubrir", "/tienda", "assets/images/perfil.jpg",
                        "linear-gradient(120deg,#4a3a30 0%,#7a5b45 100%)", "#f7ede2"),
                build("Hasta 25% de descuento", "Promociones de temporada por tiempo limitado.",
                        "Aprovechar", "/tienda", "",
                        "linear-gradient(120deg,#e7b96b 0%,#f0cf8f 100%)", "#4a3a30")
        );
    }

    private static PromoEntity build(String title, String subtitle, String cta, String link,
                                     String image, String bg, String text) {
        PromoEntity p = new PromoEntity();
        p.setTitle(title); p.setSubtitle(subtitle); p.setCta(cta); p.setLink(link);
        p.setImage(image); p.setBg(bg); p.setText(text);
        return p;
    }
}
