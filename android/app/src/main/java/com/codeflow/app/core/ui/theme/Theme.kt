package com.codeflow.app.core.ui.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

private val LightColors = lightColorScheme(
    primary = BrandIndigo,
    onPrimary = Color.White,
    primaryContainer = Color(0xFFE1E0FF),
    onPrimaryContainer = Color(0xFF0F0069),
    secondary = Teal,
    onSecondary = Color.White,
    tertiary = Color(0xFFB45309),
    onTertiary = Color.White,
    tertiaryContainer = Color(0xFFFFDCC2),
    onTertiaryContainer = Color(0xFF2E1500),
    background = LightBackground,
    surface = LightBackground,
)

private val DarkColors = darkColorScheme(
    primary = BrandIndigoLight,
    onPrimary = BrandIndigoDark,
    primaryContainer = BrandIndigoDark,
    onPrimaryContainer = Color(0xFFE1E0FF),
    secondary = TealLight,
    onSecondary = Color(0xFF003731),
    tertiary = Color(0xFFFFB77C),
    onTertiary = Color(0xFF4C2700),
    tertiaryContainer = Color(0xFF6C3A00),
    onTertiaryContainer = Color(0xFFFFDCC2),
    background = DarkBackground,
    surface = DarkBackground,
)

/** Tema de CodeFlow (claro/oscuro según el sistema). */
@Composable
fun CodeFlowTheme(
    darkTheme: Boolean = isSystemInDarkTheme(),
    content: @Composable () -> Unit,
) {
    MaterialTheme(
        colorScheme = if (darkTheme) DarkColors else LightColors,
        content = content,
    )
}
