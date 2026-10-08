package com.codeflow.app.core.di

import android.content.Context
import android.os.Build
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewmodel.initializer
import androidx.lifecycle.viewmodel.viewModelFactory
import com.codeflow.app.BuildConfig
import com.codeflow.app.core.network.ApiClient
import com.codeflow.app.core.session.SessionStore
import com.codeflow.app.data.remote.CodeFlowApi
import com.codeflow.app.data.repository.AuthRepository
import com.codeflow.app.data.repository.DefaultAuthRepository
import com.codeflow.app.data.repository.DefaultNotificationRepository
import com.codeflow.app.data.repository.DefaultProjectRepository
import com.codeflow.app.data.repository.NotificationRepository
import com.codeflow.app.data.repository.ProjectRepository
import com.codeflow.app.feature.auth.LoginViewModel
import com.codeflow.app.feature.notifications.NotificationListViewModel
import com.codeflow.app.feature.projects.ProjectListViewModel
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob

/**
 * Inyección de dependencias manual (sin Hilt): aquí se construye todo el grafo de objetos
 * de la app y las factorías de los ViewModel.
 *
 * Para añadir una pantalla nueva: crear su repositorio (si hace falta) y una factoría aquí.
 */
class AppContainer(context: Context) {

    /** Ámbito de la aplicación para trabajos que deben sobrevivir a una pantalla. */
    private val appScope = CoroutineScope(SupervisorJob() + Dispatchers.Default)

    val sessionStore = SessionStore(context.applicationContext, appScope)

    private val api: CodeFlowApi = ApiClient.create(
        baseUrl = BuildConfig.BASE_URL,
        tokenProvider = sessionStore::currentToken,
        onUnauthorized = sessionStore::onUnauthorized,
        enableBodyLogging = BuildConfig.DEBUG,
    )

    /** Se envía como `device_name` al iniciar sesión (un token Sanctum por dispositivo). */
    private val deviceName: String = "Android ${Build.MODEL}"

    // ---- Repositorios ----
    val authRepository: AuthRepository = DefaultAuthRepository(api, sessionStore)
    val projectRepository: ProjectRepository = DefaultProjectRepository(api)
    val notificationRepository: NotificationRepository = DefaultNotificationRepository(api)

    // ---- Factorías de ViewModel ----
    val loginViewModelFactory: ViewModelProvider.Factory = viewModelFactory {
        initializer { LoginViewModel(authRepository, deviceName) }
    }

    val projectListViewModelFactory: ViewModelProvider.Factory = viewModelFactory {
        initializer { ProjectListViewModel(projectRepository, notificationRepository, authRepository) }
    }

    val notificationListViewModelFactory: ViewModelProvider.Factory = viewModelFactory {
        initializer { NotificationListViewModel(notificationRepository) }
    }
}
