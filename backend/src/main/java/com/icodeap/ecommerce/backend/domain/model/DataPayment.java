package com.icodeap.ecommerce.backend.domain.model;

import jakarta.validation.constraints.NotNull;
import lombok.Data;

@Data
public class DataPayment {
    private String method;
    private String currency;
    private String description;
    @NotNull
    private Integer orderId;
}
