package com.icodeap.ecommerce.backend.infrastructure.jwt;

import com.icodeap.ecommerce.backend.infrastructure.service.CustomUserDetailService;
import io.jsonwebtoken.Claims;
import io.jsonwebtoken.JwtException;
import jakarta.servlet.FilterChain;
import jakarta.servlet.ServletException;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import lombok.extern.slf4j.Slf4j;
import org.springframework.security.authentication.UsernamePasswordAuthenticationToken;
import org.springframework.security.core.context.SecurityContextHolder;
import org.springframework.security.core.userdetails.UserDetails;
import org.springframework.stereotype.Component;
import org.springframework.web.filter.OncePerRequestFilter;

import java.io.IOException;

@Component
@Slf4j
public class JWTAuthorizationFilter extends OncePerRequestFilter {
    private final CustomUserDetailService customUserDetailService;
    private final JWTGenerator jwtGenerator;

    public JWTAuthorizationFilter(CustomUserDetailService customUserDetailService, JWTGenerator jwtGenerator) {
        this.customUserDetailService = customUserDetailService;
        this.jwtGenerator = jwtGenerator;
    }

    @Override
    protected void doFilterInternal(
            HttpServletRequest request,
            HttpServletResponse response,
            FilterChain filterChain
    ) throws ServletException, IOException {
        String header = request.getHeader(Constants.HEADER_AUTHORIZATION);

        if (header == null || !header.startsWith(Constants.TOKEN_BEARER_PREFIX)) {
            filterChain.doFilter(request, response);
            return;
        }

        try {
            String rawToken = header.substring(Constants.TOKEN_BEARER_PREFIX.length());
            Claims claims = jwtGenerator.parseToken(rawToken);
            UserDetails userDetails = customUserDetailService.loadUserByUsername(claims.getSubject());

            UsernamePasswordAuthenticationToken authentication =
                    new UsernamePasswordAuthenticationToken(userDetails, null, userDetails.getAuthorities());
            SecurityContextHolder.getContext().setAuthentication(authentication);
            filterChain.doFilter(request, response);
        } catch (JwtException | IllegalArgumentException ex) {
            SecurityContextHolder.clearContext();
            log.warn("Token JWT inválido: {}", ex.getMessage());
            response.sendError(HttpServletResponse.SC_UNAUTHORIZED, "Token inválido o vencido");
        }
    }
}
