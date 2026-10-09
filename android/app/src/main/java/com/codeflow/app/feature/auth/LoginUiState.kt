package com.codeflow.app.feature.auth

data class LoginUiState(
    /** Dirección del servidor tal como se muestra (p. ej. `192.168.1.50:8080`). */
    val server: String = "",
    val email: String = "",
    val password: String = "",
    val serverError: String? = null,
    val emailError: String? = null,
    val passwordError: String? = null,
    /** Error que no corresponde a un campo concreto (red, servidor, demasiados intentos...). */
    val generalError: String? = null,
    val isLoading: Boolean = false,
)
