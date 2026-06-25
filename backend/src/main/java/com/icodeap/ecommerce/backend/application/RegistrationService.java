package com.icodeap.ecommerce.backend.application;

import com.icodeap.ecommerce.backend.domain.model.User;
import com.icodeap.ecommerce.backend.domain.model.UserType;
import com.icodeap.ecommerce.backend.domain.port.IUserRepository;
import com.icodeap.ecommerce.backend.infrastructure.exception.BusinessException;

public class RegistrationService {
    private final IUserRepository iUserRepository;

    public RegistrationService(IUserRepository iUserRepository) {
        this.iUserRepository = iUserRepository;
    }

    public User register (User user){
        if (iUserRepository.existsByEmail(user.getEmail())) {
            throw new BusinessException("Este correo ya está registrado. Inicia sesión o usa otro correo.");
        }
        user.setUsername(user.getEmail());
        user.setUserType(UserType.USER);
        return iUserRepository.save(user);
    }
}
