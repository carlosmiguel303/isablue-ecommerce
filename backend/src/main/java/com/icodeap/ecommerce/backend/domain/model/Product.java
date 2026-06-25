package com.icodeap.ecommerce.backend.domain.model;

import lombok.AllArgsConstructor;
import lombok.Data;
import lombok.NoArgsConstructor;
import jakarta.validation.constraints.DecimalMin;
import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;

import java.math.BigDecimal;
import java.time.LocalDateTime;

@Data
@AllArgsConstructor
@NoArgsConstructor
public class Product {
    private Integer id;
    @NotBlank(message = "El nombre del producto es obligatorio")
    private String name;
    @NotBlank(message = "El código del producto es obligatorio")
    private String code;
    @NotBlank(message = "La descripción es obligatoria")
    private String description;
    private String urlImage;
    @NotNull(message = "El precio es obligatorio")
    @DecimalMin(value = "0.01", message = "El precio debe ser mayor a cero")
    private BigDecimal price;
    private LocalDateTime dateCreated;
    private LocalDateTime dateUpdated;
    private  Integer userId;
    private Integer categoryId;
}
