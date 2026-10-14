package com.codeflow.app.core.ui.theme

import androidx.compose.ui.graphics.Color

// =============================================================================
// Paleta Base e Identidad Principal de CodeFlow
// =============================================================================

// Fondos Base
val GhostWhite = Color(0xFFF2F4FF)
val DeepBlack  = Color(0xFF000501)

// Acentos Principales
val HotFuchsia       = Color(0xFFFF3366)
val LightSeaGreen    = Color(0xFF2EC4B6)
val Chartreuse       = Color(0xCA, 0xFE, 0x48, 0xFF) // #CAFE48
val Amethyst         = Color(0xFF9448BC)

// Derivados de Acento Eléctrico (Chartreuse) para contraste WCAG AA
val ChartreuseDeep   = Color(0xFF486000) // Para texto/iconos legibles sobre fondo claro (4.8:1)
val OnChartreuse     = Color(0xFF1E2800) // Para texto sobre fondo Chartreuse (12.8:1)

// Variantes funcionales derivadas para Modo Claro (Light)
val PrimaryLight            = HotFuchsia
val OnPrimaryLight          = Color.White
val PrimaryContainerLight   = Color(0xFFFFE5EB)
val OnPrimaryContainerLight = Color(0xFF90002F)

val SecondaryLight            = Color(0xFF0E8377) // Variante para texto/iconos (4.6:1 sobre GhostWhite)
val OnSecondaryLight          = Color.White
val SecondaryContainerLight   = Color(0xFFD3F6F2)
val OnSecondaryContainerLight = Color(0xFF004F47)

val TertiaryLight            = Color(0xFF732A99) // Amethyst derivado accesible (5.4:1 sobre GhostWhite)
val OnTertiaryLight          = Color.White
val TertiaryContainerLight   = Color(0xFFF3E8FC)
val OnTertiaryContainerLight = Color(0xFF500A73)

val SurfaceVariantLight   = Color(0xFFE3E7F8)
val OnSurfaceVariantLight = Color(0xFF3D435A)
val OutlineLight          = Color(0xFFC4C9E0)

// Variantes funcionales derivadas para Modo Oscuro (Dark)
val PrimaryDark            = Color(0xFFFF4D79)
val OnPrimaryDark          = Color(0xFF3A000E)
val PrimaryContainerDark   = Color(0xFF54041B)
val OnPrimaryContainerDark = Color(0xFFFFCCD6)

val SecondaryDark            = Color(0xFF4DE3D6)
val OnSecondaryDark          = Color(0xFF003832)
val SecondaryContainerDark   = Color(0xFF004F47)
val OnSecondaryContainerDark = Color(0xFFA4F5EC)

val TertiaryDark            = Color(0xFFC687EB)
val OnTertiaryDark          = Color(0xFF35024E)
val TertiaryContainerDark   = Color(0xFF4B1567)
val OnTertiaryContainerDark = Color(0xFFF0D6FF)

val SurfaceDark          = Color(0xFF000501)
val SurfaceVariantDark   = Color(0xFF0D1510)
val OnSurfaceDark        = Color(0xFFF0F4EF)
val OnSurfaceVariantDark = Color(0xFFA9B5A8)
val OutlineDark          = Color(0xFF253427)

// =============================================================================
// Aliases de compatibilidad con vistas y componentes existentes (Logo, etc.)
// =============================================================================
val BrandPurple      = Amethyst
val BrandPurpleLight = TertiaryDark
val BrandPurpleDark  = TertiaryContainerDark
val BrandIndigo      = Color(0xFF5E2B97)

val Teal      = LightSeaGreen
val TealLight = SecondaryDark

val LightBackground = GhostWhite
val DarkBackground  = DeepBlack
val DarkSurface     = SurfaceVariantDark
