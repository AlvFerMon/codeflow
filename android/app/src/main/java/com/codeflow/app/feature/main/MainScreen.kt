package com.codeflow.app.feature.main

import android.Manifest
import android.os.Build
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.List
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material3.Badge
import androidx.compose.material3.BadgedBox
import androidx.compose.material3.Icon
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.snapshotFlow
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.lifecycle.Lifecycle
import androidx.lifecycle.compose.LocalLifecycleOwner
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import androidx.lifecycle.repeatOnLifecycle
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavGraph.Companion.findStartDestination
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import com.codeflow.app.core.di.AppContainer
import com.codeflow.app.core.ui.components.AccountMenu
import com.codeflow.app.feature.activity.ActivityScreen
import com.codeflow.app.feature.notifications.NotificationListScreen
import com.codeflow.app.feature.projects.ProjectListScreen
import kotlinx.coroutines.delay
import kotlinx.coroutines.flow.first

/** Pestañas de la barra inferior. */
enum class MainTab(val route: String, val label: String, val icon: ImageVector) {
    ACTIVITY("activity", "Actividad", Icons.AutoMirrored.Filled.List),
    NOTIFICATIONS("notifications", "Notificaciones", Icons.Filled.Notifications),
    PROJECTS("projects", "Proyectos", Icons.Filled.Home),
    ;

    companion object {
        fun fromRoute(route: String?): MainTab? = entries.firstOrNull { it.route == route }
    }
}

private const val POLL_INTERVAL_MS = 30_000L

/**
 * Pantalla principal tras iniciar sesión: Actividad, Notificaciones y Proyectos.
 *
 * @param requestedTab pestaña pedida desde fuera (al pulsar un aviso de la barra del sistema).
 * @param onTabConsumed se llama cuando ya se ha navegado a [requestedTab].
 */
@Composable
fun MainScreen(
    container: AppContainer,
    requestedTab: MainTab?,
    onTabConsumed: () -> Unit,
) {
    val mainViewModel: MainViewModel = viewModel(factory = container.mainViewModelFactory)
    val unreadCount by mainViewModel.unreadCount.collectAsStateWithLifecycle()
    val userName by mainViewModel.userName.collectAsStateWithLifecycle()
    val server by mainViewModel.server.collectAsStateWithLifecycle()

    // Android 13+: pedir permiso para mostrar avisos (una vez; si se deniega, la app sigue funcionando)
    val permissionLauncher = rememberLauncherForActivityResult(ActivityResultContracts.RequestPermission()) { }
    LaunchedEffect(Unit) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU && !container.systemNotifier.canNotify()) {
            permissionLauncher.launch(Manifest.permission.POST_NOTIFICATIONS)
        }
    }

    // Sondeo cada 30 s mientras la app está en primer plano
    val lifecycleOwner = LocalLifecycleOwner.current
    LaunchedEffect(lifecycleOwner) {
        lifecycleOwner.repeatOnLifecycle(Lifecycle.State.STARTED) {
            while (true) {
                mainViewModel.poll()
                delay(POLL_INTERVAL_MS)
            }
        }
    }

    val navController = rememberNavController()
    val backStackEntry by navController.currentBackStackEntryAsState()
    val currentTab = MainTab.fromRoute(backStackEntry?.destination?.route)

    fun openTab(tab: MainTab) {
        navController.navigate(tab.route) {
            popUpTo(navController.graph.findStartDestination().id) { saveState = true }
            launchSingleTop = true
            restoreState = true
        }
    }

    LaunchedEffect(requestedTab) {
        if (requestedTab != null) {
            // En un arranque en frío el NavHost aún no tiene grafo: esperar a su primer destino
            snapshotFlow { navController.currentBackStackEntry }.first { it != null }
            openTab(requestedTab)
            onTabConsumed()
        }
    }

    val accountMenu: @Composable () -> Unit = {
        AccountMenu(userName = userName, server = server, onLogout = mainViewModel::logout)
    }

    Scaffold(
        bottomBar = {
            NavigationBar {
                MainTab.entries.forEach { tab ->
                    NavigationBarItem(
                        selected = currentTab == tab,
                        onClick = { openTab(tab) },
                        label = { Text(tab.label) },
                        icon = {
                            if (tab == MainTab.NOTIFICATIONS && unreadCount > 0) {
                                BadgedBox(badge = { Badge { Text(if (unreadCount > 99) "99+" else unreadCount.toString()) } }) {
                                    Icon(tab.icon, contentDescription = null)
                                }
                            } else {
                                Icon(tab.icon, contentDescription = null)
                            }
                        },
                    )
                }
            }
        },
        // Cada pestaña dibuja su propia barra superior (que ya respeta la barra de estado)
        contentWindowInsets = WindowInsets(0, 0, 0, 0),
    ) { padding ->
        NavHost(
            navController = navController,
            startDestination = MainTab.ACTIVITY.route,
            modifier = Modifier.padding(padding),
        ) {
            composable(MainTab.ACTIVITY.route) {
                ActivityScreen(
                    viewModel = viewModel(factory = container.activityViewModelFactory),
                    topBarActions = accountMenu,
                )
            }
            composable(MainTab.NOTIFICATIONS.route) {
                NotificationListScreen(
                    viewModel = viewModel(factory = container.notificationListViewModelFactory),
                    topBarActions = accountMenu,
                )
            }
            composable(MainTab.PROJECTS.route) {
                ProjectListScreen(
                    viewModel = viewModel(factory = container.projectListViewModelFactory),
                    topBarActions = accountMenu,
                )
            }
        }
    }
}
