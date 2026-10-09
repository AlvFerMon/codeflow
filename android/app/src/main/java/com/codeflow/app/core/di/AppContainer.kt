package com.codeflow.app.core.di

import android.content.Context
import android.os.Build
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.viewmodel.initializer
import androidx.lifecycle.viewmodel.viewModelFactory
import com.codeflow.app.BuildConfig
import com.codeflow.app.core.network.ApiClient
import com.codeflow.app.core.notifications.NotificationPoller
import com.codeflow.app.core.notifications.SystemNotifier
import com.codeflow.app.core.session.SessionStore
import com.codeflow.app.data.remote.CodeFlowApi
import com.codeflow.app.data.repository.ActivityRepository
import com.codeflow.app.data.repository.AuthRepository
import com.codeflow.app.data.repository.DefaultActivityRepository
import com.codeflow.app.data.repository.DefaultAuthRepository
import com.codeflow.app.data.repository.DefaultNotificationRepository
import com.codeflow.app.data.repository.DefaultProjectRepository
import com.codeflow.app.data.repository.NotificationRepository
import com.codeflow.app.data.repository.ProjectRepository
import com.codeflow.app.feature.activity.ActivityViewModel
import com.codeflow.app.feature.auth.LoginViewModel
import com.codeflow.app.feature.auth.VerifyEmailViewModel
import com.codeflow.app.feature.main.MainViewModel
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
    val appScope = CoroutineScope(SupervisorJob() + Dispatchers.Default)

    /** El servidor por defecto sale de local.properties (codeflow.debugApiUrl); se cambia en el login. */
    val sessionStore = SessionStore(context.applicationContext, appScope, defaultServerUrl = BuildConfig.BASE_URL)

    private val api: CodeFlowApi = ApiClient.create(
        baseUrlProvider = sessionStore::currentServerUrl,
        tokenProvider = sessionStore::currentToken,
        onUnauthorized = sessionStore::onUnauthorized,
        enableBodyLogging = BuildConfig.DEBUG,
    )

    /** Se envía como `device_name` al iniciar sesión (un token Sanctum por dispositivo). */
    private val deviceName: String = "Android ${Build.MODEL}"

    // ---- Repositorios ----

    val authRepository: AuthRepository = DefaultAuthRepository(api, sessionStore)
    val activityRepository: ActivityRepository = DefaultActivityRepository(api)
    val projectRepository: ProjectRepository = DefaultProjectRepository(api)
    val notificationRepository: NotificationRepository = DefaultNotificationRepository(api)

    // ---- Notificaciones del sistema ----

    val systemNotifier = SystemNotifier(context)
    val notificationPoller = NotificationPoller(sessionStore, notificationRepository, systemNotifier)

    // ---- Factorías de ViewModel ----

    val loginViewModelFactory: ViewModelProvider.Factory = viewModelFactory {
        initializer { LoginViewModel(authRepository, sessionStore, deviceName) }
    }

    val verifyEmailViewModelFactory: ViewModelProvider.Factory = viewModelFactory {
        initializer { VerifyEmailViewModel(authRepository) }
    }

    val mainViewModelFactory: ViewModelProvider.Factory = viewModelFactory {
        initializer { MainViewModel(sessionStore, authRepository, notificationRepository, notificationPoller) }
    }

    val activityViewModelFactory: ViewModelProvider.Factory = viewModelFactory {
        initializer { ActivityViewModel(activityRepository, projectRepository) }
    }

    val projectListViewModelFactory: ViewModelProvider.Factory = viewModelFactory {
        initializer { ProjectListViewModel(projectRepository) }
    }

    val notificationListViewModelFactory: ViewModelProvider.Factory = viewModelFactory {
        initializer { NotificationListViewModel(notificationRepository) }
    }
}
