package com.codeflow.app.feature.auth

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.codeflow.app.core.network.ApiResult
import com.codeflow.app.data.repository.AuthRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch

data class VerifyEmailUiState(
    val code: String = "",
    val error: String? = null,
    /** Mensaje informativo (p. ej. "Te hemos enviado un código nuevo..."). */
    val info: String? = null,
    val isLoading: Boolean = false,
    val isResending: Boolean = false,
)

/**
 * Cuenta sin verificar: hay que escribir el código de 6 dígitos que llega por correo
 * (en local, a Mailpit: http://localhost:8025). Al verificarlo, la sesión pasa a verificada y
 * la navegación muestra la app.
 */
class VerifyEmailViewModel(
    private val authRepository: AuthRepository,
) : ViewModel() {

    private val _uiState = MutableStateFlow(VerifyEmailUiState())
    val uiState: StateFlow<VerifyEmailUiState> = _uiState.asStateFlow()

    fun onCodeChange(value: String) {
        val digits = value.filter { it.isDigit() }.take(CODE_LENGTH)
        _uiState.update { it.copy(code = digits, error = null) }
    }

    fun submit() {
        val current = _uiState.value
        if (current.isLoading) return
        if (current.code.length != CODE_LENGTH) {
            _uiState.update { it.copy(error = "El código tiene $CODE_LENGTH dígitos.") }
            return
        }
        _uiState.update { it.copy(isLoading = true, error = null, info = null) }
        viewModelScope.launch {
            when (val result = authRepository.verifyEmail(current.code)) {
                is ApiResult.Success -> _uiState.update { it.copy(isLoading = false) }
                is ApiResult.Error -> _uiState.update {
                    it.copy(isLoading = false, error = result.firstErrorFor("code") ?: result.message)
                }
            }
        }
    }

    fun resend() {
        if (_uiState.value.isResending) return
        _uiState.update { it.copy(isResending = true, error = null, info = null) }
        viewModelScope.launch {
            when (val result = authRepository.resendVerificationCode()) {
                is ApiResult.Success -> _uiState.update { it.copy(isResending = false, info = result.data, code = "") }
                is ApiResult.Error -> _uiState.update { it.copy(isResending = false, error = result.message) }
            }
        }
    }

    fun logout() {
        viewModelScope.launch { authRepository.logout() }
    }

    companion object {
        const val CODE_LENGTH = 6
    }
}
