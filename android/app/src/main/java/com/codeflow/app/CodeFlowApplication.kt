package com.codeflow.app

import android.app.Application
import com.codeflow.app.core.di.AppContainer

/**
 * Punto de entrada de la app: crea el [AppContainer] (inyección de dependencias manual)
 * una sola vez por proceso.
 */
class CodeFlowApplication : Application() {

    lateinit var container: AppContainer
        private set

    override fun onCreate() {
        super.onCreate()
        container = AppContainer(this)
    }
}
