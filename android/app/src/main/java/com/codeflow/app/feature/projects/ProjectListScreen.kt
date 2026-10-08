package com.codeflow.app.feature.projects

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ExitToApp
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Badge
import androidx.compose.material3.BadgedBox
import androidx.compose.material3.Card
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.codeflow.app.core.ui.components.EmptyView
import com.codeflow.app.core.ui.components.ErrorView
import com.codeflow.app.core.ui.components.LoadingView
import com.codeflow.app.core.ui.formatApiDateTime
import com.codeflow.app.core.ui.theme.CodeFlowTheme
import com.codeflow.app.domain.model.Project

@Composable
fun ProjectListScreen(
    viewModel: ProjectListViewModel,
    onOpenNotifications: () -> Unit,
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()

    // Se ejecuta cada vez que la pantalla vuelve a mostrarse (p. ej. al volver de Notificaciones).
    LaunchedEffect(Unit) { viewModel.refreshUnreadCount() }

    ProjectListContent(
        uiState = uiState,
        onRetry = viewModel::load,
        onLoadMore = viewModel::loadMore,
        onOpenNotifications = onOpenNotifications,
        onLogout = viewModel::logout,
    )
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun ProjectListContent(
    uiState: ProjectListUiState,
    onRetry: () -> Unit,
    onLoadMore: () -> Unit,
    onOpenNotifications: () -> Unit,
    onLogout: () -> Unit,
) {
    var showLogoutDialog by remember { mutableStateOf(false) }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Mis proyectos") },
                actions = {
                    IconButton(onClick = onRetry, enabled = !uiState.isLoading) {
                        Icon(Icons.Filled.Refresh, contentDescription = "Recargar")
                    }
                    IconButton(onClick = onOpenNotifications) {
                        BadgedBox(
                            badge = {
                                if (uiState.unreadNotifications > 0) {
                                    Badge {
                                        Text(
                                            if (uiState.unreadNotifications > 99) "99+"
                                            else uiState.unreadNotifications.toString(),
                                        )
                                    }
                                }
                            },
                        ) {
                            Icon(Icons.Filled.Notifications, contentDescription = "Notificaciones")
                        }
                    }
                    IconButton(onClick = { showLogoutDialog = true }, enabled = !uiState.isLoggingOut) {
                        Icon(Icons.AutoMirrored.Filled.ExitToApp, contentDescription = "Cerrar sesión")
                    }
                },
            )
        },
    ) { padding ->
        val contentModifier = Modifier
            .fillMaxSize()
            .padding(padding)

        when {
            uiState.isLoading && uiState.projects.isEmpty() -> LoadingView(contentModifier)
            uiState.error != null && uiState.projects.isEmpty() ->
                ErrorView(message = uiState.error, onRetry = onRetry, modifier = contentModifier)
            uiState.projects.isEmpty() ->
                EmptyView(
                    message = "Todavía no participas en ningún proyecto.",
                    modifier = contentModifier,
                    icon = Icons.Filled.Home,
                )
            else -> LazyColumn(
                modifier = contentModifier,
                contentPadding = PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp),
            ) {
                items(uiState.projects, key = { it.id }) { project ->
                    ProjectCard(project)
                }
                if (uiState.canLoadMore || uiState.loadMoreError != null) {
                    item(key = "footer") {
                        ListFooter(
                            loadedPages = uiState.currentPage,
                            isLoadingMore = uiState.isLoadingMore,
                            error = uiState.loadMoreError,
                            onLoadMore = onLoadMore,
                        )
                    }
                }
            }
        }
    }

    if (showLogoutDialog) {
        AlertDialog(
            onDismissRequest = { showLogoutDialog = false },
            title = { Text("Cerrar sesión") },
            text = { Text("¿Seguro que quieres cerrar la sesión en este dispositivo?") },
            confirmButton = {
                TextButton(onClick = {
                    showLogoutDialog = false
                    onLogout()
                }) { Text("Cerrar sesión") }
            },
            dismissButton = {
                TextButton(onClick = { showLogoutDialog = false }) { Text("Cancelar") }
            },
        )
    }
}

@Composable
private fun ProjectCard(project: Project) {
    // TODO(fase 7): al pulsar, abrir los repositorios del proyecto.
    Card(modifier = Modifier.fillMaxWidth()) {
        Column(Modifier.padding(16.dp)) {
            Text(
                text = project.name,
                style = MaterialTheme.typography.titleMedium,
                fontWeight = FontWeight.SemiBold,
            )
            if (!project.description.isNullOrBlank()) {
                Spacer(Modifier.height(4.dp))
                Text(
                    text = project.description,
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                    maxLines = 2,
                    overflow = TextOverflow.Ellipsis,
                )
            }
            val meta = listOfNotNull(
                project.ownerName?.let { "Propietario: $it" },
                project.updatedAt?.let { "Actualizado: ${formatApiDateTime(it)}" },
            ).joinToString(" · ")
            if (meta.isNotEmpty()) {
                Spacer(Modifier.height(8.dp))
                Text(
                    text = meta,
                    style = MaterialTheme.typography.labelMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
        }
    }
}

/** Pie de lista: dispara la carga de la página siguiente al hacerse visible. */
@Composable
private fun ListFooter(
    loadedPages: Int,
    isLoadingMore: Boolean,
    error: String?,
    onLoadMore: () -> Unit,
) {
    Box(
        modifier = Modifier
            .fillMaxWidth()
            .padding(8.dp),
        contentAlignment = Alignment.Center,
    ) {
        if (error != null) {
            TextButton(onClick = onLoadMore) { Text("$error Reintentar") }
        } else {
            // Se vuelve a lanzar cada vez que llega una página nueva (cambia loadedPages).
            LaunchedEffect(loadedPages) { onLoadMore() }
            if (isLoadingMore) CircularProgressIndicator()
        }
    }
}

@Preview(showBackground = true)
@Composable
private fun ProjectListPreview() {
    CodeFlowTheme {
        ProjectListContent(
            uiState = ProjectListUiState(
                isLoading = false,
                projects = listOf(
                    Project(1, "CodeFlow", "Plataforma colaborativa de desarrollo", "Ana", null),
                    Project(2, "TFG", null, "Luis", "2026-10-08T10:00:00.000000Z"),
                ),
                unreadNotifications = 3,
            ),
            onRetry = {},
            onLoadMore = {},
            onOpenNotifications = {},
            onLogout = {},
        )
    }
}

@Preview(showBackground = true)
@Composable
private fun ProjectListEmptyPreview() {
    CodeFlowTheme {
        EmptyView(message = "Todavía no participas en ningún proyecto.", icon = Icons.Filled.Home)
    }
}
