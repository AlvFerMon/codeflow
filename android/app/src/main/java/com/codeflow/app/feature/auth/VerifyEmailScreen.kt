package com.codeflow.app.feature.auth

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.imePadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.layout.widthIn
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardActions
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalFocusManager
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.input.ImeAction
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.compose.collectAsStateWithLifecycle
import com.codeflow.app.core.ui.components.CodeFlowLogo
import com.codeflow.app.core.ui.theme.CodeFlowTheme

@Composable
fun VerifyEmailScreen(viewModel: VerifyEmailViewModel) {
    val uiState by viewModel.uiState.collectAsStateWithLifecycle()
    VerifyEmailContent(
        uiState = uiState,
        onCodeChange = viewModel::onCodeChange,
        onSubmit = viewModel::submit,
        onResend = viewModel::resend,
        onLogout = viewModel::logout,
    )
}

@Composable
private fun VerifyEmailContent(
    uiState: VerifyEmailUiState,
    onCodeChange: (String) -> Unit,
    onSubmit: () -> Unit,
    onResend: () -> Unit,
    onLogout: () -> Unit,
) {
    val focusManager = LocalFocusManager.current

    Scaffold { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
                .imePadding()
                .verticalScroll(rememberScrollState())
                .padding(24.dp),
            verticalArrangement = Arrangement.Center,
            horizontalAlignment = Alignment.CenterHorizontally,
        ) {
            CodeFlowLogo()
            Spacer(Modifier.height(24.dp))
            Text("Verifica tu correo", style = MaterialTheme.typography.titleLarge)
            Spacer(Modifier.height(8.dp))
            Text(
                text = "Escribe el código de 6 dígitos que te hemos enviado por correo.",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                textAlign = TextAlign.Center,
            )
            Spacer(Modifier.height(24.dp))
            Column(modifier = Modifier.widthIn(max = 480.dp), horizontalAlignment = Alignment.CenterHorizontally) {
                OutlinedTextField(
                    value = uiState.code,
                    onValueChange = onCodeChange,
                    label = { Text("Código") },
                    placeholder = { Text("123456") },
                    isError = uiState.error != null,
                    supportingText = { uiState.error?.let { Text(it) } },
                    singleLine = true,
                    enabled = !uiState.isLoading,
                    textStyle = TextStyle(fontSize = 24.sp, letterSpacing = 8.sp, textAlign = TextAlign.Center),
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.NumberPassword, imeAction = ImeAction.Done),
                    keyboardActions = KeyboardActions(onDone = {
                        focusManager.clearFocus()
                        onSubmit()
                    }),
                    modifier = Modifier.fillMaxWidth(),
                )
                if (uiState.info != null) {
                    Text(
                        text = uiState.info,
                        color = MaterialTheme.colorScheme.primary,
                        style = MaterialTheme.typography.bodyMedium,
                        textAlign = TextAlign.Center,
                    )
                }
                Spacer(Modifier.height(16.dp))
                Button(
                    onClick = {
                        focusManager.clearFocus()
                        onSubmit()
                    },
                    enabled = !uiState.isLoading,
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(48.dp),
                ) {
                    if (uiState.isLoading) {
                        CircularProgressIndicator(modifier = Modifier.size(20.dp), strokeWidth = 2.dp)
                    } else {
                        Text("Verificar")
                    }
                }
                Spacer(Modifier.height(8.dp))
                TextButton(onClick = onResend, enabled = !uiState.isResending) {
                    Text(if (uiState.isResending) "Enviando..." else "Reenviar código")
                }
                TextButton(onClick = onLogout) { Text("Usar otra cuenta") }
            }
        }
    }
}

@Preview(showBackground = true)
@Composable
private fun VerifyEmailPreview() {
    CodeFlowTheme {
        VerifyEmailContent(
            uiState = VerifyEmailUiState(code = "1234", info = "Te hemos enviado un código nuevo a ana@codeflow.test."),
            onCodeChange = {},
            onSubmit = {},
            onResend = {},
            onLogout = {},
        )
    }
}
