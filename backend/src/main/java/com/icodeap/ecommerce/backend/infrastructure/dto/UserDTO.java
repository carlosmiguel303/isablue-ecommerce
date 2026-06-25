package com.icodeap.ecommerce.backend.infrastructure.dto;

import jakarta.validation.constraints.NotBlank;

public record UserDTO(
        @NotBlank(message = "Ingresa tu correo") String username,
        @NotBlank(message = "Ingresa tu contraseña") String password
) {
}
