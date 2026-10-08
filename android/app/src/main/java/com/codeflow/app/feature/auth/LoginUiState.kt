package com.codeflow.app.feature.auth

data class LoginUiState(
    val email: String = "",
    val password: String = "",
    val emailError: String? = null,
    val passwordError: String? = null,
    /** Error que no corresponde a un campo concreto (red, servidor, demasiados intentos...). */
    val generalError: String? = null,
    val isLoading: Boolean = false,
)
