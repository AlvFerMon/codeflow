package com.codeflow.app.core.notifications

import android.content.Context
import androidx.work.Constraints
import androidx.work.CoroutineWorker
import androidx.work.ExistingPeriodicWorkPolicy
import androidx.work.NetworkType
import androidx.work.PeriodicWorkRequestBuilder
import androidx.work.WorkManager
import androidx.work.WorkerParameters
import com.codeflow.app.CodeFlowApplication
import java.util.concurrent.TimeUnit

/**
 * Sondeo en segundo plano (WorkManager): cada 15 minutos, el mínimo que permite Android.
 * Con la app abierta se sondea además cada 30 s desde la pantalla principal.
 */
class NotificationWorker(
    context: Context,
    params: WorkerParameters,
) : CoroutineWorker(context, params) {

    override suspend fun doWork(): Result {
        val container = (applicationContext as CodeFlowApplication).container
        container.notificationPoller.poll()
        // Los fallos de red no se reintentan: el siguiente periodo lo volverá a intentar.
        return Result.success()
    }

    companion object {
        private const val WORK_NAME = "codeflow-notifications"

        fun schedule(context: Context) {
            val request = PeriodicWorkRequestBuilder<NotificationWorker>(15, TimeUnit.MINUTES)
                .setConstraints(Constraints.Builder().setRequiredNetworkType(NetworkType.CONNECTED).build())
                .build()
            WorkManager.getInstance(context)
                .enqueueUniquePeriodicWork(WORK_NAME, ExistingPeriodicWorkPolicy.KEEP, request)
        }

        fun cancel(context: Context) {
            WorkManager.getInstance(context).cancelUniqueWork(WORK_NAME)
        }
    }
}
