package com.codeflow.app.feature.notifications

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Done
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Warning
import androidx.compose.material3.Badge
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.SnackbarHost
import androidx.compose.material3.SnackbarHostState
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.codeflow.app.core.ui.components.EmptyView
import com.codeflow.app.core.ui.components.ErrorView
import com.codeflow.app.core.ui.components.LoadingView
import com.codeflow.app.core.ui.formatRelativeDateTime
import com.codeflow.app.core.ui.theme.CodeFlowTheme
import com.codeflow.app.domain.model.AppNotification
import com.codeflow.app.domain.model.NotificationKind

@Composable
fun NotificationListScreen(
    viewModel: NotificationListViewModel,
    topBarActions: @Composable () -> Unit = {},
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()
    NotificationListContent(
        uiState = uiState,
        onRetry = viewModel::load,
        onMarkAllRead = viewModel::markAllAsRead,
        topBarActions = topBarActions,
        onLoadMore = viewModel::loadMore,
        onNotificationClick = viewModel::onNotificationClick,
        onMessageShown = viewModel::onMessageShown,
    )
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun NotificationListContent(
    uiState: NotificationListUiState,
    onRetry: () -> Unit,
    onMarkAllRead: () -> Unit,
    topBarActions: @Composable () -> Unit,
    onLoadMore: () -> Unit,
    onNotificationClick: (AppNotification) -> Unit,
    onMessageShown: () -> Unit,
) {
    val snackbarHostState = remember { SnackbarHostState() }

    LaunchedEffect(uiState.message) {
        val message = uiState.message
        if (message != null) {
            snackbarHostState.showSnackbar(message)
            onMessageShown()
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Notificaciones") },
                actions = {
                    IconButton(onClick = onMarkAllRead, enabled = uiState.notifications.any { !it.isRead }) {
                        Icon(Icons.Filled.Done, contentDescription = "Marcar todas como leídas")
                    }
                    IconButton(onClick = onRetry, enabled = !uiState.isLoading) {
                        Icon(Icons.Filled.Refresh, contentDescription = "Recargar")
                    }
                    topBarActions()
                },
            )
        },
        // La barra inferior de la pantalla principal ya reserva el espacio del sistema
        contentWindowInsets = WindowInsets(0, 0, 0, 0),
        snackbarHost = { SnackbarHost(snackbarHostState) },
    ) { padding ->
        val contentModifier = Modifier
            .fillMaxSize()
            .padding(padding)

        when {
            uiState.isLoading && uiState.notifications.isEmpty() -> LoadingView(contentModifier)
            uiState.error != null && uiState.notifications.isEmpty() ->
                ErrorView(message = uiState.error, onRetry = onRetry, modifier = contentModifier)
            uiState.notifications.isEmpty() -> EmptyView(
                message = "No tienes notificaciones.",
                modifier = contentModifier,
                icon = Icons.Filled.Notifications,
            )
            else -> LazyColumn(
                modifier = contentModifier,
                contentPadding = PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(8.dp),
            ) {
                items(uiState.notifications, key = { it.id }) { notification ->
                    NotificationCard(
                        notification = notification,
                        onClick = { onNotificationClick(notification) },
                    )
                }
                if (uiState.canLoadMore || uiState.loadMoreError != null) {
                    item(key = "footer") {
                        Box(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(8.dp),
                            contentAlignment = Alignment.Center,
                        ) {
                            val loadMoreError = uiState.loadMoreError
                            if (loadMoreError != null) {
                                TextButton(onClick = onLoadMore) { Text("$loadMoreError Reintentar") }
                            } else {
                                LaunchedEffect(uiState.currentPage) { onLoadMore() }
                                if (uiState.isLoadingMore) CircularProgressIndicator()
                            }
                        }
                    }
                }
            }
        }
    }
}

/**
 * Las alertas se resaltan con el color de error y un icono de aviso; los cambios normales
 * usan el color terciario si no se han leído. Las leídas quedan con el fondo neutro.
 */
@Composable
private fun NotificationCard(
    notification: AppNotification,
    onClick: () -> Unit,
) {
    val colors = MaterialTheme.colorScheme
    val isAlert = notification.kind == NotificationKind.ALERT

    val containerColor: Color
    val contentColor: Color
    when {
        isAlert -> {
            containerColor = if (notification.isRead) colors.errorContainer.copy(alpha = 0.45f) else colors.errorContainer
            contentColor = colors.onErrorContainer
        }
        !notification.isRead -> {
            containerColor = colors.tertiaryContainer
            contentColor = colors.onTertiaryContainer
        }
        else -> {
            containerColor = colors.surfaceVariant
            contentColor = colors.onSurfaceVariant
        }
    }

    Card(
        onClick = onClick,
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = containerColor, contentColor = contentColor),
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.Top,
        ) {
            Icon(
                imageVector = if (isAlert) Icons.Filled.Warning else Icons.Filled.Notifications,
                contentDescription = if (isAlert) "Alerta" else "Cambio",
                tint = if (isAlert) colors.error else contentColor,
            )
            Spacer(Modifier.width(12.dp))
            Column(Modifier.weight(1f)) {
                Text(
                    text = notification.title,
                    style = MaterialTheme.typography.titleSmall,
                    fontWeight = if (notification.isRead) FontWeight.Normal else FontWeight.Bold,
                )
                if (!notification.message.isNullOrBlank()) {
                    Spacer(Modifier.height(2.dp))
                    Text(
                        text = notification.message,
                        style = MaterialTheme.typography.bodyMedium,
                        maxLines = 3,
                        overflow = TextOverflow.Ellipsis,
                    )
                }
                val meta = listOfNotNull(
                    notification.actorName,
                    notification.projectName,
                    notification.createdAt?.let { formatRelativeDateTime(it) },
                ).joinToString(" · ")
                if (meta.isNotEmpty()) {
                    Spacer(Modifier.height(6.dp))
                    Text(text = meta, style = MaterialTheme.typography.labelSmall)
                }
            }
            if (!notification.isRead) {
                Spacer(Modifier.width(8.dp))
                // Punto de "no leída"
                Badge(
                    modifier = Modifier.size(10.dp),
                    containerColor = if (isAlert) colors.error else colors.primary,
                )
            }
        }
    }
}

@Preview(showBackground = true)
@Composable
private fun NotificationListPreview() {
    CodeFlowTheme {
        NotificationListContent(
            uiState = NotificationListUiState(
                isLoading = false,
                notifications = listOf(
                    AppNotification(
                        id = "1", kind = NotificationKind.ALERT, event = "pull_request.conflict",
                        title = "Conflicto en el PR #4", message = "La rama main ha cambiado.",
                        url = null, isRead = false, createdAt = "2026-10-08T10:00:00.000000Z",
                        actorName = "Ana", projectName = "CodeFlow",
                    ),
                    AppNotification(
                        id = "2", kind = NotificationKind.CHANGE, event = "commit.created",
                        title = "Nuevo commit en develop", message = "Arregla el login",
                        url = null, isRead = true, createdAt = "2026-10-07T18:30:00.000000Z",
                        actorName = "Luis", projectName = "CodeFlow",
                    ),
                ),
            ),
            onRetry = {},
            onMarkAllRead = {},
            topBarActions = {},
            onLoadMore = {},
            onNotificationClick = {},
            onMessageShown = {},
        )
    }
}
