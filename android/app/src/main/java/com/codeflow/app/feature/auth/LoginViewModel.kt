package com.codeflow.app.feature.auth

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.codeflow.app.core.network.ApiResult
import com.codeflow.app.core.network.ServerUrl
import com.codeflow.app.core.session.ServerConfig
import com.codeflow.app.data.repository.AuthRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

/**
 * Login con email y contraseña. Si va bien, el repositorio guarda el token en la sesión y
 * la navegación (que observa la sesión) lleva automáticamente a la lista de proyectos.
 */
class LoginViewModel(
    private val authRepository: AuthRepository,
    private val serverConfig: ServerConfig,
    private val deviceName: String,
) : ViewModel() {

    private val _uiState = MutableStateFlow(LoginUiState(server = ServerUrl.display(serverConfig.serverUrl.value)))
    val uiState: StateFlow<LoginUiState> = _uiState.asStateFlow()

    fun onServerChange(value: String) {
        _uiState.update { it.copy(server = value, serverError = null, generalError = null) }
    }

    fun onEmailChange(value: String) {
        _uiState.update { it.copy(email = value, emailError = null, generalError = null) }
    }

    fun onPasswordChange(value: String) {
        _uiState.update { it.copy(password = value, passwordError = null, generalError = null) }
    }

    fun submit() {
        val current = _uiState.value
        if (current.isLoading) return

        val email = current.email.trim()
        val emailError = validateEmail(email)
        val passwordError = validatePassword(current.password)
        val serverUrl = ServerUrl.normalize(current.server)
        if (serverUrl == null || emailError != null || passwordError != null) {
            _uiState.update {
                it.copy(
                    serverError = if (serverUrl == null) "Escribe la dirección del servidor, p. ej. 192.168.1.50:8080." else null,
                    emailError = emailError,
                    passwordError = passwordError,
                    generalError = null,
                )
            }
            return
        }

        _uiState.update {
            it.copy(email = email, isLoading = true, serverError = null, emailError = null, passwordError = null, generalError = null)
        }

        viewModelScope.launch {
            serverConfig.saveServerUrl(serverUrl)
            when (val result = authRepository.login(email, current.password, deviceName)) {
                is ApiResult.Success -> _uiState.update { it.copy(isLoading = false, password = "") }
                is ApiResult.Error -> {
                    // Laravel devuelve "Credenciales incorrectas." como error del campo email (422).
                    val serverEmailError = result.firstErrorFor("email")
                    val serverPasswordError = result.firstErrorFor("password")
                    _uiState.update {
                        it.copy(
                            isLoading = false,
                            emailError = serverEmailError,
                            passwordError = serverPasswordError,
                            generalError = if (serverEmailError == null && serverPasswordError == null) {
                                if (result.isNetworkError) {
                                    "No se pudo conectar con ${ServerUrl.display(serverUrl)}. " +
                                        "Comprueba la dirección y que el móvil esté en la misma Wi-Fi que el PC."
                                } else {
                                    result.message
                                }
                            } else {
                                null
                            },
                        )
                    }
                }
            }
        }
    }

    companion object {
        private val EMAIL_REGEX = Regex("^[^@\\s]+@[^@\\s]+\\.[^@\\s]+$")

        fun validateEmail(email: String): String? = when {
            email.isBlank() -> "Introduce tu correo electrónico."
            !EMAIL_REGEX.matches(email) -> "El correo electrónico no es válido."
            else -> null
        }

        fun validatePassword(password: String): String? =
            if (password.isEmpty()) "Introduce tu contraseña." else null
    }
}
