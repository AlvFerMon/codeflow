package com.codeflow.app.feature.activity

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.layout.FlowRow
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.List
import androidx.compose.material.icons.filled.ArrowDropDown
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DropdownMenu
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.TopAppBar
import androidx.compose.material3.pulltorefresh.PullToRefreshBox
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.SpanStyle
import androidx.compose.ui.text.buildAnnotatedString
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.withStyle
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.codeflow.app.core.ui.components.EmptyView
import com.codeflow.app.core.ui.components.ErrorView
import com.codeflow.app.core.ui.components.LoadingView
import com.codeflow.app.core.ui.formatRelativeDateTime
import com.codeflow.app.core.ui.theme.CodeFlowTheme
import com.codeflow.app.domain.model.ActivityCategory
import com.codeflow.app.domain.model.ActivityItem
import com.codeflow.app.domain.model.Project

@Composable
fun ActivityScreen(
    viewModel: ActivityViewModel,
    topBarActions: @Composable () -> Unit = {},
) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()
    ActivityContent(
        uiState = uiState,
        onRetry = viewModel::load,
        onRefresh = viewModel::refresh,
        onLoadMore = viewModel::loadMore,
        onSelectProject = viewModel::selectProject,
        topBarActions = topBarActions,
    )
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun ActivityContent(
    uiState: ActivityUiState,
    onRetry: () -> Unit,
    onRefresh: () -> Unit,
    onLoadMore: () -> Unit,
    onSelectProject: (Long?) -> Unit,
    topBarActions: @Composable () -> Unit,
) {
    Scaffold(
        topBar = { TopAppBar(title = { Text("Actividad") }, actions = { topBarActions() }) },
        // La barra inferior de la pantalla principal ya reserva el espacio del sistema
        contentWindowInsets = WindowInsets(0, 0, 0, 0),
    ) { padding ->
        Column(
            Modifier
                .fillMaxSize()
                .padding(padding),
        ) {
            if (uiState.projects.size > 1) {
                ProjectFilter(uiState.projects, uiState.selectedProjectId, onSelectProject)
            }

            val contentModifier = Modifier.fillMaxSize()
            when {
                uiState.isLoading && uiState.items.isEmpty() -> LoadingView(contentModifier)
                uiState.error != null && uiState.items.isEmpty() ->
                    ErrorView(message = uiState.error, onRetry = onRetry, modifier = contentModifier)
                else -> PullToRefreshBox(
                    isRefreshing = uiState.isRefreshing,
                    onRefresh = onRefresh,
                    modifier = contentModifier,
                ) {
                    if (uiState.items.isEmpty()) {
                        EmptyView(
                            message = "Todavía no hay actividad en tus proyectos.",
                            icon = Icons.AutoMirrored.Filled.List,
                        )
                    } else {
                        ActivityList(uiState, onLoadMore)
                    }
                }
            }
        }
    }
}

@Composable
private fun ActivityList(uiState: ActivityUiState, onLoadMore: () -> Unit) {
    LazyColumn(
        modifier = Modifier.fillMaxSize(),
        contentPadding = PaddingValues(vertical = 8.dp),
    ) {
        uiState.error?.let { error ->
            item(key = "error") {
                Text(
                    text = error,
                    color = MaterialTheme.colorScheme.error,
                    style = MaterialTheme.typography.bodySmall,
                    modifier = Modifier.padding(horizontal = 16.dp, vertical = 4.dp),
                )
            }
        }
        items(uiState.items, key = { it.id }) { item ->
            ActivityRow(item)
            HorizontalDivider(Modifier.padding(start = 68.dp), color = MaterialTheme.colorScheme.outlineVariant)
        }
        if (uiState.canLoadMore || uiState.loadMoreError != null) {
            item(key = "footer") {
                Box(
                    Modifier
                        .fillMaxWidth()
                        .padding(12.dp),
                    contentAlignment = Alignment.Center,
                ) {
                    val error = uiState.loadMoreError
                    if (error != null) {
                        TextButton(onClick = onLoadMore) { Text("$error Reintentar") }
                    } else {
                        LaunchedEffect(uiState.currentPage) { onLoadMore() }
                        if (uiState.isLoadingMore) CircularProgressIndicator(Modifier.size(24.dp))
                    }
                }
            }
        }
    }
}

@Composable
private fun ProjectFilter(projects: List<Project>, selectedId: Long?, onSelect: (Long?) -> Unit) {
    var expanded by remember { mutableStateOf(false) }
    val selectedName = projects.firstOrNull { it.id == selectedId }?.name ?: "Todos los proyectos"

    Box(Modifier.padding(horizontal = 16.dp, vertical = 4.dp)) {
        FilterChip(
            selected = selectedId != null,
            onClick = { expanded = true },
            label = { Text(selectedName) },
            trailingIcon = { Icon(Icons.Filled.ArrowDropDown, contentDescription = null) },
        )
        DropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
            DropdownMenuItem(text = { Text("Todos los proyectos") }, onClick = {
                expanded = false
                onSelect(null)
            })
            projects.forEach { project ->
                DropdownMenuItem(text = { Text(project.name) }, onClick = {
                    expanded = false
                    onSelect(project.id)
                })
            }
        }
    }
}

/** Una fila del feed: "Ana subió 1 cambio a la rama main" + proyecto, repositorio, rama y hora. */
@OptIn(ExperimentalLayoutApi::class)
@Composable
private fun ActivityRow(item: ActivityItem) {
    val colors = MaterialTheme.colorScheme
    val accent = categoryColor(item.category)

    Row(
        Modifier
            .fillMaxWidth()
            .padding(horizontal = 16.dp, vertical = 12.dp),
    ) {
        Box(
            modifier = Modifier
                .size(40.dp)
                .clip(CircleShape)
                .background(accent.copy(alpha = 0.15f)),
            contentAlignment = Alignment.Center,
        ) {
            Text(
                text = item.actorName?.firstOrNull()?.uppercase() ?: "?",
                color = accent,
                fontWeight = FontWeight.Bold,
            )
        }
        Spacer(Modifier.width(12.dp))
        Column(Modifier.weight(1f)) {
            Text(
                text = buildAnnotatedString {
                    withStyle(SpanStyle(fontWeight = FontWeight.SemiBold)) { append(item.actorName ?: "Alguien") }
                    append(" ")
                    append(item.summary)
                },
                style = MaterialTheme.typography.bodyMedium,
            )
            Spacer(Modifier.height(6.dp))
            FlowRow(
                horizontalArrangement = Arrangement.spacedBy(6.dp),
                verticalArrangement = Arrangement.spacedBy(4.dp),
            ) {
                item.projectName?.let { Tag(it) }
                item.repositoryName?.let { Tag(it) }
                item.branch?.let { branch ->
                    Tag(if (item.targetBranch != null) "$branch → ${item.targetBranch}" else branch, accent = colors.primary)
                }
                item.commitSha?.let { Tag(it, monospace = true) }
                Text(
                    text = formatRelativeDateTime(item.createdAt),
                    style = MaterialTheme.typography.labelSmall,
                    color = colors.onSurfaceVariant,
                    modifier = Modifier.align(Alignment.CenterVertically),
                )
            }
        }
    }
}

@Composable
private fun Tag(text: String, accent: Color? = null, monospace: Boolean = false) {
    val colors = MaterialTheme.colorScheme
    Text(
        text = text,
        style = MaterialTheme.typography.labelSmall,
        fontFamily = if (monospace) FontFamily.Monospace else null,
        color = accent ?: colors.onSurfaceVariant,
        maxLines = 1,
        modifier = Modifier
            .clip(RoundedCornerShape(6.dp))
            .background((accent ?: colors.onSurfaceVariant).copy(alpha = 0.10f))
            .padding(horizontal = 6.dp, vertical = 2.dp),
    )
}

@Composable
private fun categoryColor(category: ActivityCategory): Color {
    val colors = MaterialTheme.colorScheme
    return when (category) {
        ActivityCategory.COMMIT, ActivityCategory.BRANCH -> colors.primary
        ActivityCategory.PULL_REQUEST -> colors.secondary
        ActivityCategory.ISSUE -> colors.tertiary
        else -> colors.onSurfaceVariant
    }
}

@Preview(showBackground = true)
@Composable
private fun ActivityPreview() {
    CodeFlowTheme {
        ActivityContent(
            uiState = ActivityUiState(
                isLoading = false,
                items = listOf(
                    ActivityItem(
                        1, "commit.created", ActivityCategory.COMMIT, "María", "subió 1 cambio a la rama main",
                        1, "Proyecto DAM", "app", "main", null, "a1b2c3d", "2026-10-09T10:00:00.000000Z",
                    ),
                    ActivityItem(
                        2, "pull_request.opened", ActivityCategory.PULL_REQUEST, "Ana", "abrió el pull request #3",
                        1, "Proyecto DAM", "app", "feature/login", "main", null, "2026-10-09T09:00:00.000000Z",
                    ),
                ),
            ),
            onRetry = {},
            onRefresh = {},
            onLoadMore = {},
            onSelectProject = {},
            topBarActions = {},
        )
    }
}
