package com.codeflow.app.core.notifications

import android.Manifest
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.content.ContextCompat
import com.codeflow.app.MainActivity
import com.codeflow.app.R
import com.codeflow.app.domain.model.AppNotification
import com.codeflow.app.domain.model.NotificationKind

/** Muestra avisos en la barra del sistema. Al pulsarlos se abre la pestaña Notificaciones. */
class SystemNotifier(context: Context) {

    private val context = context.applicationContext

    /** Canales (Android 8+). Se llama una vez al arrancar la app. */
    fun createChannels() {
        val manager = context.getSystemService(NotificationManager::class.java) ?: return
        manager.createNotificationChannel(
            NotificationChannel(CHANNEL_CHANGES, "Cambios en tus proyectos", NotificationManager.IMPORTANCE_DEFAULT)
                .apply { description = "Commits, ramas, pull requests e issues de tus proyectos" },
        )
        manager.createNotificationChannel(
            NotificationChannel(CHANNEL_ALERTS, "Alertas", NotificationManager.IMPORTANCE_HIGH)
                .apply { description = "Revisiones pedidas, conflictos y cambios de permisos" },
        )
    }

    fun canNotify(): Boolean =
        Build.VERSION.SDK_INT < Build.VERSION_CODES.TIRAMISU ||
            ContextCompat.checkSelfPermission(context, Manifest.permission.POST_NOTIFICATIONS) ==
            PackageManager.PERMISSION_GRANTED

    fun show(notifications: List<AppNotification>) {
        if (!canNotify()) return
        val manager = NotificationManagerCompat.from(context)
        try {
            // Como mucho 5 avisos sueltos; si hay más, uno resumen.
            notifications.take(MAX_INDIVIDUAL).forEach { manager.notify(it.id.hashCode(), build(it)) }
            if (notifications.size > MAX_INDIVIDUAL) {
                manager.notify(SUMMARY_ID, buildSummary(notifications.size))
            }
        } catch (e: SecurityException) {
            // El usuario retiró el permiso entre la comprobación y el aviso: no se muestra nada.
        }
    }

    fun cancelAll() {
        NotificationManagerCompat.from(context).cancelAll()
    }

    private fun build(notification: AppNotification) =
        NotificationCompat.Builder(
            context,
            if (notification.kind == NotificationKind.ALERT) CHANNEL_ALERTS else CHANNEL_CHANGES,
        )
            .setSmallIcon(R.drawable.ic_stat_codeflow)
            .setColor(BRAND_COLOR)
            .setContentTitle(notification.title.ifBlank { "CodeFlow" })
            .setContentText(notification.message)
            .setStyle(NotificationCompat.BigTextStyle().bigText(notification.message))
            .setSubText(notification.projectName)
            .setContentIntent(openNotificationsIntent())
            .setAutoCancel(true)
            .setGroup(GROUP)
            .build()

    private fun buildSummary(total: Int) =
        NotificationCompat.Builder(context, CHANNEL_CHANGES)
            .setSmallIcon(R.drawable.ic_stat_codeflow)
            .setColor(BRAND_COLOR)
            .setContentTitle("CodeFlow")
            .setContentText("Tienes $total notificaciones nuevas")
            .setContentIntent(openNotificationsIntent())
            .setAutoCancel(true)
            .setGroup(GROUP)
            .setGroupSummary(true)
            .build()

    private fun openNotificationsIntent(): PendingIntent {
        val intent = Intent(context, MainActivity::class.java)
            .putExtra(MainActivity.EXTRA_OPEN_TAB, MainActivity.TAB_NOTIFICATIONS)
            .addFlags(Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP or Intent.FLAG_ACTIVITY_SINGLE_TOP)
        return PendingIntent.getActivity(
            context,
            0,
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE,
        )
    }

    private companion object {
        const val CHANNEL_CHANGES = "changes"
        const val CHANNEL_ALERTS = "alerts"
        const val GROUP = "codeflow"
        const val SUMMARY_ID = 1
        const val MAX_INDIVIDUAL = 5
        const val BRAND_COLOR = 0xFF9810FA.toInt()
    }
}
