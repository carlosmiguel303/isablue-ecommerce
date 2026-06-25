package com.icodeap.ecommerce.backend.infrastructure.config;

import com.icodeap.ecommerce.backend.domain.model.UserType;
import com.icodeap.ecommerce.backend.infrastructure.adapter.ICategoryCrudRepository;
import com.icodeap.ecommerce.backend.infrastructure.adapter.IProductCrudRepository;
import com.icodeap.ecommerce.backend.infrastructure.adapter.IUserCrudRepository;
import com.icodeap.ecommerce.backend.infrastructure.entity.CategoryEntity;
import com.icodeap.ecommerce.backend.infrastructure.entity.ProductEntity;
import com.icodeap.ecommerce.backend.infrastructure.entity.UserEntity;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.boot.CommandLineRunner;
import org.springframework.boot.autoconfigure.condition.ConditionalOnProperty;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.security.crypto.password.PasswordEncoder;

import java.math.BigDecimal;

@Configuration
public class DataInitializer {

    @Bean
    @ConditionalOnProperty(name = "app.demo-data.enabled", havingValue = "true")
    CommandLineRunner initDemoData(
            ICategoryCrudRepository categories,
            IProductCrudRepository products,
            IUserCrudRepository users,
            PasswordEncoder encoder,
            @Value("${app.backend.base-url:http://localhost:8085}") String backendBaseUrl,
            @Value("${app.demo.admin.email:admin@isablue.pe}") String adminEmail,
            @Value("${app.demo.admin.password:admin123}") String adminPassword,
            @Value("${app.demo.user.email:cliente@isablue.pe}") String userEmail,
            @Value("${app.demo.user.password:cliente123}") String userPassword
    ) {
        return args -> {
            if (users.count() == 0) {
                users.save(createUser(adminEmail, "Administrador", "IsaBlue", "999999999", adminPassword, UserType.ADMIN, encoder));
                users.save(createUser(userEmail, "Cliente", "Demo", "988888888", userPassword, UserType.USER, encoder));
            }

            if (categories.count() == 0) {
                String[] names = {"Juguetes Didácticos", "Bebés", "Creatividad", "Vehículos", "Dinosaurios"};
                for (String name : names) {
                    CategoryEntity category = new CategoryEntity();
                    category.setName(name);
                    categories.save(category);
                }
            }

            if (products.count() == 0 && users.count() > 0 && categories.count() >= 5) {
                CategoryEntity didacticos = categories.findById(1).orElseThrow();
                CategoryEntity bebes = categories.findById(2).orElse(didacticos);
                CategoryEntity creatividad = categories.findById(3).orElse(didacticos);
                CategoryEntity vehiculos = categories.findById(4).orElse(didacticos);
                CategoryEntity dinosaurios = categories.findById(5).orElse(didacticos);
                UserEntity admin = users.findByEmail(adminEmail).orElseThrow();
                String baseUrl = stripTrailingSlash(backendBaseUrl);

                add(products, admin, didacticos, "Set Montessori de Madera", "ISA-MONT-001", "Juguete didáctico para coordinación, colores y motricidad fina.", baseUrl + "/images/toy-montessori.svg", "89.00");
                add(products, admin, didacticos, "Rompecabezas Infantil Animales", "ISA-PUZ-002", "Puzzle educativo para estimular memoria, concentración y paciencia.", baseUrl + "/images/toy-puzzle.svg", "45.00");
                add(products, admin, vehiculos, "Carrito Didáctico de Madera", "ISA-CAR-003", "Vehículo seguro y resistente para juego imaginativo.", baseUrl + "/images/toy-car.svg", "59.00");
                add(products, admin, bebes, "Osito Sensorial Bebé", "ISA-BEBE-004", "Peluche suave para estimulación temprana y apego seguro.", baseUrl + "/images/toy-teddy.svg", "69.00");
                add(products, admin, creatividad, "Bloques de Construcción Creativa", "ISA-BLOCK-005", "Piezas coloridas para creatividad, lógica y trabajo en equipo.", baseUrl + "/images/toy-blocks.svg", "79.00");
                add(products, admin, dinosaurios, "Dinosaurio Educativo", "ISA-DINO-006", "Figura infantil para aprender jugando y desarrollar imaginación.", baseUrl + "/images/toy-dino.svg", "55.00");
            }
        };
    }

    private UserEntity createUser(
            String email,
            String firstName,
            String lastName,
            String cellphone,
            String password,
            UserType type,
            PasswordEncoder encoder
    ) {
        UserEntity user = new UserEntity();
        user.setUsername(email);
        user.setEmail(email);
        user.setFirstName(firstName);
        user.setLastName(lastName);
        user.setAddress("Lima, Perú");
        user.setCellphone(cellphone);
        user.setPassword(encoder.encode(password));
        user.setUserType(type);
        return user;
    }

    private void add(
            IProductCrudRepository repository,
            UserEntity user,
            CategoryEntity category,
            String name,
            String code,
            String description,
            String image,
            String price
    ) {
        ProductEntity product = new ProductEntity();
        product.setName(name);
        product.setCode(code);
        product.setDescription(description);
        product.setUrlImage(image);
        product.setPrice(new BigDecimal(price));
        product.setUserEntity(user);
        product.setCategoryEntity(category);
        repository.save(product);
    }

    private String stripTrailingSlash(String value) {
        return value.endsWith("/") ? value.substring(0, value.length() - 1) : value;
    }
}
