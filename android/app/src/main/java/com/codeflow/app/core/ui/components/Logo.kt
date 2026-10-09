package com.codeflow.app.core.ui.components

import androidx.compose.foundation.Canvas
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.size
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.geometry.Size
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.StrokeJoin
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.text.SpanStyle
import androidx.compose.ui.text.buildAnnotatedString
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.withStyle
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.TextUnit
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.codeflow.app.core.ui.theme.BrandIndigo
import com.codeflow.app.core.ui.theme.BrandPurple

/**
 * Monograma "CF" de CodeFlow (el mismo que en la web, docs/branding): la C usa el color del
 * texto (se adapta al modo oscuro) y la F es una rama con dos commits.
 * Dibujado sobre una cuadrícula de 64×64.
 */
@Composable
fun CodeFlowMark(modifier: Modifier = Modifier, size: Dp = 40.dp, inkColor: Color = MaterialTheme.colorScheme.onSurface) {
    Canvas(modifier.size(size)) {
        val u = this.size.width / 64f
        val stroke = Stroke(width = 6f * u, cap = StrokeCap.Round, join = StrokeJoin.Round)

        // C: semicírculo izquierdo de centro (30, 32) y radio 18
        drawArc(
            color = inkColor,
            startAngle = 90f,
            sweepAngle = 180f,
            useCenter = false,
            topLeft = Offset(12f * u, 14f * u),
            size = Size(36f * u, 36f * u),
            style = stroke,
        )

        // F: tronco y brazos (la rama)
        val branch = Path().apply {
            moveTo(38f * u, 50f * u)
            lineTo(38f * u, 14f * u)
            lineTo(52f * u, 14f * u)
            moveTo(38f * u, 32f * u)
            lineTo(48f * u, 32f * u)
        }
        drawPath(branch, color = BrandPurple, style = stroke)

        // Commits
        drawCircle(BrandPurple, radius = 4.5f * u, center = Offset(52f * u, 14f * u))
        drawCircle(BrandIndigo, radius = 4.5f * u, center = Offset(48f * u, 32f * u))
    }
}

/** Monograma + "CodeFlow" ("Flow" en el color de marca). */
@Composable
fun CodeFlowLogo(modifier: Modifier = Modifier, markSize: Dp = 44.dp, fontSize: TextUnit = 28.sp) {
    Row(modifier, verticalAlignment = Alignment.CenterVertically, horizontalArrangement = Arrangement.spacedBy(6.dp)) {
        CodeFlowMark(size = markSize)
        Text(
            text = buildAnnotatedString {
                append("Code")
                withStyle(SpanStyle(color = MaterialTheme.colorScheme.primary)) { append("Flow") }
            },
            fontSize = fontSize,
            fontWeight = FontWeight.Bold,
            color = MaterialTheme.colorScheme.onSurface,
        )
    }
}
