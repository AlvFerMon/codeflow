package com.codeflow.app

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.activity.enableEdgeToEdge
import com.codeflow.app.core.ui.theme.CodeFlowTheme
import com.codeflow.app.navigation.CodeFlowNavHost

class MainActivity : ComponentActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()

        val container = (application as CodeFlowApplication).container

        setContent {
            CodeFlowTheme {
                CodeFlowNavHost(container = container)
            }
        }
    }
}
