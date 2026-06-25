package com.icodeap.ecommerce.backend.application;

import com.icodeap.ecommerce.backend.domain.model.Product;
import com.icodeap.ecommerce.backend.domain.port.IProductRepository;
import org.springframework.web.multipart.MultipartFile;

import java.io.IOException;

public class ProductService {
    private final IProductRepository productRepository;
    private final UploadFile uploadFile;

    public ProductService(IProductRepository productRepository, UploadFile uploadFile) {
        this.productRepository = productRepository;
        this.uploadFile = uploadFile;
    }

    public Product save(Product product, MultipartFile multipartFile) throws IOException {
        boolean isUpdate = product.getId() != null && product.getId() > 0;

        if (isUpdate && multipartFile != null && !multipartFile.isEmpty()) {
            uploadFile.deleteByUrl(product.getUrlImage());
            product.setUrlImage(uploadFile.upload(multipartFile));
        } else if (!isUpdate) {
            product.setUrlImage(uploadFile.upload(multipartFile));
        }

        return productRepository.save(product);
    }

    public Iterable<Product> findAll() {
        return productRepository.findAll();
    }

    public Product findById(Integer id) {
        return productRepository.findById(id);
    }

    public void deleteById(Integer id) {
        Product product = findById(id);
        try {
            uploadFile.deleteByUrl(product.getUrlImage());
        } catch (IOException ignored) {
            // La eliminación del producto no debe bloquearse por un archivo faltante.
        }
        productRepository.deleteById(id);
    }
}
