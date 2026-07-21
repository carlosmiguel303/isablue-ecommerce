package com.icodeap.ecommerce.backend.infrastructure.entity;

import jakarta.persistence.*;
import lombok.Data;

import java.util.List;

/**
 * Página informativa editable por el administrador (formas de entrega,
 * cambios y devoluciones, etc.). El cliente la ve; el admin la edita.
 */
@Entity
@Table(name = "info_pages")
@Data
public class InfoPageEntity {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer id;

    @Column(name = "page_key", unique = true)
    private String pageKey;
    private String title;
    @Column(columnDefinition = "TEXT")
    private String content;

    public static InfoPageEntity of(String key, String title, String content) {
        InfoPageEntity p = new InfoPageEntity();
        p.setPageKey(key);
        p.setTitle(title);
        p.setContent(content);
        return p;
    }

    public static List<InfoPageEntity> defaults() {
        return List.of(
                of("entregas", "Formas de entrega",
                        "En Isablue puedes recibir tu pedido de dos formas:\n\n" +
                        "ENVÍO A DOMICILIO\n" +
                        "Llevamos tu pedido hasta la puerta de tu casa, a todo el Perú. " +
                        "El costo y el tiempo de entrega se coordinan por WhatsApp según tu distrito.\n\n" +
                        "RECOJO EN TIENDA\n" +
                        "También puedes recoger tu pedido en nuestra tienda.\n" +
                        "Dirección: José María Barreto.\n" +
                        "Coordina el día y la hora por WhatsApp (920097746).\n\n" +
                        "Apenas realices tu pedido y tu Yape, te contactamos por WhatsApp para coordinar la entrega o el recojo."),
                of("devoluciones", "Cambios y devoluciones",
                        "En Isablue queremos que quedes feliz con tu compra.\n\n" +
                        "CAMBIOS\n" +
                        "Si tu producto tiene alguna falla de fábrica, puedes solicitar el cambio dentro de los 7 días " +
                        "de recibido, presentando tu comprobante. El producto debe estar sin uso y en su empaque original.\n\n" +
                        "DEVOLUCIONES\n" +
                        "Para devoluciones, escríbenos por WhatsApp (920097746) dentro de los 7 días. " +
                        "Evaluaremos tu caso y te indicaremos los pasos a seguir.\n\n" +
                        "No aplican cambios ni devoluciones por mal uso del producto.\n\n" +
                        "Cualquier duda, escríbenos por WhatsApp — estamos para ayudarte.")
        );
    }

    public static InfoPageEntity defaultFor(String key) {
        return defaults().stream().filter(p -> p.getPageKey().equals(key)).findFirst()
                .orElseGet(() -> of(key, "Información", ""));
    }
}
