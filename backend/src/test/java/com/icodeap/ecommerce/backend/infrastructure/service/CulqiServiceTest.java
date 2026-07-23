package com.icodeap.ecommerce.backend.infrastructure.service;

import org.junit.jupiter.api.Test;

import java.math.BigDecimal;

import static org.junit.jupiter.api.Assertions.*;

/**
 * Regla de seguridad de la Parte A: sin llaves de Culqi, el cargo SIMULADO solo
 * se permite cuando payments.simulated-card.enabled=true (demos locales).
 * En producción (flag=false) un cargo sin pasarela debe RECHAZARSE, de modo que
 * nunca confirme un pedido sin cobro real.
 */
class CulqiServiceTest {

    @Test
    void simulatedChargeRejectedWhenDisabled() {
        // secretKey vacío (no configurado), simulado deshabilitado (como en producción)
        CulqiService service = new CulqiService("", "", false);

        assertThrows(IllegalStateException.class,
                () -> service.charge(new BigDecimal("50.00"), "PEN", "cliente@demo.local", null));
    }

    @Test
    void simulatedChargeAllowedWhenEnabled() {
        CulqiService service = new CulqiService("", "", true);

        String ref = service.charge(new BigDecimal("50.00"), "PEN", "cliente@demo.local", null);
        assertTrue(ref.startsWith("SIM-"));
    }

    @Test
    void notConfiguredWhenNoSecretKey() {
        assertFalse(new CulqiService("", "pk_test", false).isConfigured());
        assertTrue(new CulqiService("sk_test_123", "pk_test", false).isConfigured());
    }
}
