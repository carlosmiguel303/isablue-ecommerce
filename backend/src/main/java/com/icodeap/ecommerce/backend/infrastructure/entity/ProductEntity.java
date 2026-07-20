package com.icodeap.ecommerce.backend.infrastructure.entity;

import jakarta.persistence.*;
import lombok.Data;
import lombok.NoArgsConstructor;
import org.hibernate.annotations.CreationTimestamp;
import org.hibernate.annotations.UpdateTimestamp;

import java.math.BigDecimal;
import java.time.LocalDateTime;

@Entity
@Table(name = "products")
@Data
@NoArgsConstructor
public class ProductEntity {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer id;
    private String name;
    @Column(nullable = false, unique = true, length = 60)
    private String code;
    private String description;
    private String urlImage;
    @Column(nullable = false, precision = 12, scale = 2)
    private BigDecimal price;
    @CreationTimestamp
    private LocalDateTime dateCreated;
    @UpdateTimestamp
    private LocalDateTime dateUpdated;

    @ManyToOne
    private UserEntity userEntity;
    @ManyToOne
    private CategoryEntity categoryEntity;
}
