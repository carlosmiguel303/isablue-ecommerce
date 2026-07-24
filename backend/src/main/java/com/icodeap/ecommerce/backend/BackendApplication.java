package com.icodeap.ecommerce.backend;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.context.ApplicationContext;
import org.springframework.context.annotation.AnnotationConfigApplicationContext;

import java.util.TimeZone;

@SpringBootApplication
public class BackendApplication {

	public static void main(String[] args) {
		// El servidor (Railway) corre en UTC. Fijamos la zona horaria de la tienda
		// para que las fechas de pedidos, pagos y boletas se guarden en hora local.
		// Configurable con la variable de entorno APP_TIMEZONE (por defecto, Perú).
		String timezone = System.getenv().getOrDefault("APP_TIMEZONE", "America/Lima");
		TimeZone.setDefault(TimeZone.getTimeZone(timezone));
		SpringApplication.run(BackendApplication.class, args);
	}

}
