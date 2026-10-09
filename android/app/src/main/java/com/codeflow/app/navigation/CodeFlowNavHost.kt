package com.codeflow.app.navigation

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.key
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewmodel.compose.viewModel
import com.codeflow.app.core.di.AppContainer
import com.codeflow.app.core.session.SessionEvent
import com.codeflow.app.core.session.SessionState
import com.codeflow.app.core.ui.components.LoadingView
import com.codeflow.app.feature.auth.LoginScreen
import com.codeflow.app.feature.auth.VerifyEmailScreen
import com.codeflow.app.feature.main.MainScreen
import com.codeflow.app.feature.main.MainTab

/**
 * Raíz de la navegación. La sesión es la única fuente de verdad:
 *  - sin token → Login;
 *  - con token pero email sin verificar → código de verificación;
 *  - con token y verificado → pantalla principal (Actividad / Notificaciones / Proyectos).
 * Un logout o un 401 (token caducado o revocado) devuelve al Login automáticamente.
 */
@Composable
fun CodeFlowNavHost(
    container: AppContainer,
    requestedTab: MainTab? = null,
    onTabConsumed: () -> Unit = {},
) {
    val sessionState by container.sessionStore.state.collectAsStateWithLifecycle()
    val snackbarHostState = remember { SnackbarHostState() }

    LaunchedEffect(Unit) {
        container.sessionStore.events.collect { event ->
            when (event) {
                SessionEvent.Expired ->
                    snackbarHostState.showSnackbar("Tu sesión ha caducado. Vuelve a iniciar sesión.")
            }
        }
    }

    Box(Modifier.fillMaxSize()) {
        when (val state = sessionState) {
            SessionState.Loading -> LoadingView()
            SessionState.LoggedOut ->
                LoginScreen(viewModel = viewModel(factory = container.loginViewModelFactory))
            is SessionState.LoggedIn ->
                if (!state.emailVerified) {
                    VerifyEmailScreen(viewModel = viewModel(factory = container.verifyEmailViewModelFactory))
                } else {
                    // Una pantalla principal nueva por sesión (token): no se reutiliza el estado de otra cuenta
                    key(state.token) {
                        MainScreen(container = container, requestedTab = requestedTab, onTabConsumed = onTabConsumed)
                    }
                }
        }

        SnackbarHost(
            hostState = snackbarHostState,
            modifier = Modifier
                .align(Alignment.BottomCenter)
                .navigationBarsPadding(),
        )
    }
}
