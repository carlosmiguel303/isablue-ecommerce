package com.icodeap.ecommerce.backend.infrastructure.adapter;

import com.icodeap.ecommerce.backend.domain.model.User;
import com.icodeap.ecommerce.backend.domain.port.IUserRepository;
import com.icodeap.ecommerce.backend.infrastructure.mapper.UserMapper;
import com.icodeap.ecommerce.backend.infrastructure.exception.ResourceNotFoundException;
import org.springframework.stereotype.Repository;

@Repository
public class UserCrudRepositoryImpl implements IUserRepository {
    private final IUserCrudRepository iUserCrudRepository;
    private final UserMapper userMapper;

    public UserCrudRepositoryImpl(IUserCrudRepository iUserCrudRepository, UserMapper userMapper) {
        this.iUserCrudRepository = iUserCrudRepository;
        this.userMapper = userMapper;
    }

    @Override
    public User save(User user) {
        return userMapper.toUser(iUserCrudRepository.save( userMapper.toUserEntity(user) ));
    }

    @Override
    public User findByEmail(String email) {
        return userMapper.toUser(iUserCrudRepository.findByEmail(email).orElseThrow(
                ()-> new ResourceNotFoundException("No existe una cuenta registrada con el correo: "+email)
        )   );
    }

    @Override
    public User findById(Integer id) {
        return  userMapper.toUser(iUserCrudRepository.findById(id).orElseThrow(() -> new ResourceNotFoundException("Usuario no encontrado")));
    }

    @Override
    public boolean existsByEmail(String email) {
        return iUserCrudRepository.existsByEmail(email);
    }
}
