package com.codeflow.app.feature.projects

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Refresh
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
    topBarActions: @Composable () -> Unit = {},
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()

    ProjectListContent(
        uiState = uiState,
        onRetry = viewModel::load,
        onLoadMore = viewModel::loadMore,
        topBarActions = topBarActions,
    )
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun ProjectListContent(
    uiState: ProjectListUiState,
    onRetry: () -> Unit,
    onLoadMore: () -> Unit,
    topBarActions: @Composable () -> Unit,
) {

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Mis proyectos") },
                actions = {
                    IconButton(onClick = onRetry, enabled = !uiState.isLoading) {
                        Icon(Icons.Filled.Refresh, contentDescription = "Recargar")
                    }
                    topBarActions()
                },
            )
        },
        // La barra inferior de la pantalla principal ya reserva el espacio del sistema
        contentWindowInsets = WindowInsets(0, 0, 0, 0),
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
            ),
            onRetry = {},
            onLoadMore = {},
            topBarActions = {},
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
