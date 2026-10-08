package com.codeflow.app.navigation

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.navigationBarsPadding
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavHostController
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import com.codeflow.app.core.di.AppContainer
import com.codeflow.app.core.session.SessionEvent
import com.codeflow.app.core.session.SessionState
import com.codeflow.app.core.ui.components.LoadingView
import com.codeflow.app.feature.auth.LoginScreen
import com.codeflow.app.feature.notifications.NotificationListScreen
import com.codeflow.app.feature.projects.ProjectListScreen

/** Rutas de navegación. Las de fases futuras se añadirán aquí (repos, ramas, commits, issues, PRs...). */
object Routes {
    const val LOGIN = "login"
    const val PROJECTS = "projects"
    const val NOTIFICATIONS = "notifications"
}

/**
 * Raíz de la navegación. Espera a leer la sesión de DataStore y arranca en Login (sin token)
 * o en Proyectos (con token).
 */
@Composable
fun CodeFlowNavHost(container: AppContainer) {
    val sessionState by container.sessionStore.state.collectAsStateWithLifecycle()

    if (sessionState is SessionState.Loading) {
        LoadingView()
    } else {
        CodeFlowNavGraph(
            container = container,
            startLoggedIn = sessionState is SessionState.LoggedIn,
            isLoggedIn = sessionState is SessionState.LoggedIn,
        )
    }
}

@Composable
private fun CodeFlowNavGraph(
    container: AppContainer,
    startLoggedIn: Boolean,
    isLoggedIn: Boolean,
    navController: NavHostController = rememberNavController(),
) {
    // El destino inicial se fija una sola vez; después la sesión dirige la navegación.
    val startDestination = remember { if (startLoggedIn) Routes.PROJECTS else Routes.LOGIN }
    val snackbarHostState = remember { SnackbarHostState() }

    // La sesión es la única fuente de verdad: login correcto → Proyectos;
    // logout o 401 (token caducado/revocado) → Login, vaciando la pila.
    LaunchedEffect(isLoggedIn) {
        val currentRoute = navController.currentDestination?.route ?: return@LaunchedEffect
        if (isLoggedIn && currentRoute == Routes.LOGIN) {
            navController.navigate(Routes.PROJECTS) {
                popUpTo(Routes.LOGIN) { inclusive = true }
            }
        } else if (!isLoggedIn && currentRoute != Routes.LOGIN) {
            navController.navigate(Routes.LOGIN) {
                popUpTo(navController.graph.id) { inclusive = true }
            }
        }
    }

    LaunchedEffect(Unit) {
        container.sessionStore.events.collect { event ->
            when (event) {
                SessionEvent.Expired ->
                    snackbarHostState.showSnackbar("Tu sesión ha caducado. Vuelve a iniciar sesión.")
            }
        }
    }

    Box(Modifier.fillMaxSize()) {
        NavHost(navController = navController, startDestination = startDestination) {
            composable(Routes.LOGIN) {
                LoginScreen(viewModel = viewModel(factory = container.loginViewModelFactory))
            }
            composable(Routes.PROJECTS) {
                ProjectListScreen(
                    viewModel = viewModel(factory = container.projectListViewModelFactory),
                    onOpenNotifications = {
                        navController.navigate(Routes.NOTIFICATIONS) { launchSingleTop = true }
                    },
                )
            }
            composable(Routes.NOTIFICATIONS) {
                NotificationListScreen(
                    viewModel = viewModel(factory = container.notificationListViewModelFactory),
                    // popBackStack hasta Proyectos (y no un pop "a ciegas") evita vaciar la pila con un doble toque.
                    onBack = { navController.popBackStack(Routes.PROJECTS, inclusive = false) },
                )
            }
            // TODO(fases siguientes): repositorios, ramas, commits (con diff), issues, PRs, revisiones.
        }

        SnackbarHost(
            hostState = snackbarHostState,
            modifier = Modifier
                .align(Alignment.BottomCenter)
                .navigationBarsPadding(),
        )
    }
}
