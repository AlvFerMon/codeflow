package com.codeflow.app

import android.content.Intent
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import androidx.compose.runtime.mutableStateOf
import com.codeflow.app.core.ui.theme.CodeFlowTheme
import com.codeflow.app.feature.main.MainTab
import com.codeflow.app.navigation.CodeFlowNavHost

class MainActivity : ComponentActivity() {

    /** Pestaña pedida por un aviso de la barra del sistema (se consume al navegar). */
    private val requestedTab = mutableStateOf<MainTab?>(null)

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        handleIntent(intent)

        val container = (application as CodeFlowApplication).container
        setContent {
            CodeFlowTheme {
                CodeFlowNavHost(
                    container = container,
                    requestedTab = requestedTab.value,
                    onTabConsumed = { requestedTab.value = null },
                )
            }
        }
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        handleIntent(intent)
    }

    private fun handleIntent(intent: Intent?) {
        if (intent == null) return
        if (intent.getStringExtra(EXTRA_OPEN_TAB) == TAB_NOTIFICATIONS) {
            requestedTab.value = MainTab.NOTIFICATIONS
            intent.removeExtra(EXTRA_OPEN_TAB)
        }
    }

    companion object {
        const val EXTRA_OPEN_TAB = "open_tab"
        const val TAB_NOTIFICATIONS = "notifications"
    }
}
