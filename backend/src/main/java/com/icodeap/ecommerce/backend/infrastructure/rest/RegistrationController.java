package com.icodeap.ecommerce.backend.infrastructure.rest;

import com.icodeap.ecommerce.backend.application.RegistrationService;
import com.icodeap.ecommerce.backend.domain.model.User;
import jakarta.validation.Valid;
import lombok.extern.slf4j.Slf4j;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/v1/security")
@Slf4j
public class RegistrationController {
    private final RegistrationService registrationService;
    private  final BCryptPasswordEncoder passwordEncoder;

    public RegistrationController(RegistrationService registrationService, BCryptPasswordEncoder passwordEncoder) {
        this.registrationService = registrationService;
        this.passwordEncoder = passwordEncoder;
    }

    @PostMapping("/register")
    public ResponseEntity<User> register (@Valid @RequestBody User user){
        user.setPassword(passwordEncoder.encode(user.getPassword()));
        return new ResponseEntity<>(registrationService.register(user), HttpStatus.CREATED);
    }

}
