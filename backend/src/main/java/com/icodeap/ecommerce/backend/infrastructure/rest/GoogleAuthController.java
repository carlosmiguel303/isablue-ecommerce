package com.icodeap.ecommerce.backend.infrastructure.rest;

import com.icodeap.ecommerce.backend.application.RegistrationService;
import com.icodeap.ecommerce.backend.application.UserService;
import com.icodeap.ecommerce.backend.domain.model.User;
import com.icodeap.ecommerce.backend.infrastructure.dto.JWTClient;
import com.icodeap.ecommerce.backend.infrastructure.exception.BusinessException;
import com.icodeap.ecommerce.backend.infrastructure.jwt.JWTGenerator;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.client.RestTemplate;

import java.util.List;
import java.util.Map;
import java.util.UUID;

/**
 * Inicio de sesión con Google (Google Identity Services).
 * El frontend obtiene un "credential" (ID token) de Google y lo envía aquí;
 * se valida contra Google, se crea la cuenta si es nueva y se devuelve la sesión.
 */
@RestController
@RequestMapping("/api/v1/security/google")
public class GoogleAuthController {

    @Value("${google.client-id:}")
    private String googleClientId;

    private final UserService userService;
    private final RegistrationService registrationService;
    private final BCryptPasswordEncoder passwordEncoder;
    private final JWTGenerator jwtGenerator;
    private final RestTemplate rest = new RestTemplate();

    public GoogleAuthController(UserService userService, RegistrationService registrationService,
                               BCryptPasswordEncoder passwordEncoder, JWTGenerator jwtGenerator) {
        this.userService = userService;
        this.registrationService = registrationService;
        this.passwordEncoder = passwordEncoder;
        this.jwtGenerator = jwtGenerator;
    }

    /** El frontend usa esta llave pública para mostrar el botón de Google. */
    @GetMapping("/config")
    public Map<String, String> config() {
        return Map.of("clientId", googleClientId == null ? "" : googleClientId);
    }

    @PostMapping
    public JWTClient login(@RequestBody Map<String, String> body) {
        String credential = body.get("credential");
        if (credential == null || credential.isBlank()) {
            throw new BusinessException("Falta el token de acceso de Google.");
        }

        Map<?, ?> info;
        try {
            info = rest.getForObject("https://oauth2.googleapis.com/tokeninfo?id_token=" + credential, Map.class);
        } catch (Exception e) {
            throw new BusinessException("No pudimos validar tu acceso con Google. Intenta de nuevo.");
        }
        if (info == null) {
            throw new BusinessException("No pudimos validar tu acceso con Google.");
        }

        String aud = str(info.get("aud"));
        String email = str(info.get("email"));
        String given = str(info.get("given_name"));
        String family = str(info.get("family_name"));

        if (googleClientId != null && !googleClientId.isBlank() && !googleClientId.equals(aud)) {
            throw new BusinessException("El acceso con Google no es válido para esta tienda.");
        }
        if (email == null || email.isBlank()) {
            throw new BusinessException("Google no proporcionó un correo válido.");
        }

        User user;
        try {
            user = userService.findByEmail(email);
        } catch (Exception notFound) {
            User nuevo = new User();
            nuevo.setEmail(email);
            nuevo.setUsername(email);
            nuevo.setFirstName(given.isBlank() ? "Cliente" : given);
            nuevo.setLastName(family.isBlank() ? "Isablue" : family);
            nuevo.setAddress("-");
            nuevo.setCellphone("-");
            nuevo.setPassword(passwordEncoder.encode(UUID.randomUUID().toString()));
            user = registrationService.register(nuevo);
        }

        String token = jwtGenerator.getToken(email, List.of("ROLE_" + user.getUserType().name()));
        return new JWTClient(user.getId(), token, user.getUserType().toString());
    }

    private String str(Object o) { return o == null ? "" : o.toString(); }
}
