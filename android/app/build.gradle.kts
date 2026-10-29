import java.util.Properties
import org.jetbrains.kotlin.gradle.dsl.JvmTarget

plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.android)
    alias(libs.plugins.kotlin.compose)
    alias(libs.plugins.kotlin.serialization)
}

// local.properties (no versionado) permite sobrescribir la URL de la API en debug,
// por ejemplo para un móvil físico: codeflow.debugApiUrl=http://192.168.1.50:8080/api/
val localProperties = Properties().apply {
    val file = rootProject.file("local.properties")
    if (file.exists()) file.inputStream().use { load(it) }
}
val debugApiUrl: String =
    localProperties.getProperty("codeflow.debugApiUrl") ?: "http://10.0.2.2:8080/api/"

// Firma de la versión publicada. android/keystore.properties y el .jks NO se suben al repositorio:
//   storeFile=C:/Users/<tú>/codeflow-release.jks
//   storePassword=…
//   keyAlias=codeflow
//   keyPassword=…
val keystoreProperties = Properties().apply {
    val file = rootProject.file("keystore.properties")
    if (file.exists()) file.inputStream().use { load(it) }
}

android {
    namespace = "com.codeflow.app"
    compileSdk = 35

    defaultConfig {
        applicationId = "com.codeflow.app"
        minSdk = 26
        targetSdk = 35
        versionCode = 1
        versionName = "1.0.0"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
    }

    signingConfigs {
        if (keystoreProperties.containsKey("storeFile")) {
            create("release") {
                storeFile = file(keystoreProperties.getProperty("storeFile"))
                storePassword = keystoreProperties.getProperty("storePassword")
                keyAlias = keystoreProperties.getProperty("keyAlias")
                keyPassword = keystoreProperties.getProperty("keyPassword")
            }
        }
    }

    buildTypes {
        debug {
            // Servidor por defecto (se puede cambiar en el login). Emulador de Android Studio →
            // 10.0.2.2 es el "localhost" del PC (Docker en :8080)
            buildConfigField("String", "BASE_URL", "\"$debugApiUrl\"")
        }
        release {
            // Valor por defecto: el servidor se puede cambiar desde la pantalla de login.
            buildConfigField("String", "BASE_URL", "\"https://codeflow-backend-wheat.vercel.app/api/\"")
            isMinifyEnabled = false
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro",
            )
            // Firmada con keystore.properties si existe (gradlew assembleRelease); si no, también
            // se puede firmar desde Android Studio (Build > Generate Signed Bundle/APK).
            signingConfigs.findByName("release")?.let { signingConfig = it }
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    buildFeatures {
        compose = true
        buildConfig = true
    }

    testOptions {
        // Las clases de android.jar devuelven valores por defecto en los tests JVM
        unitTests.isReturnDefaultValues = true
    }

    packaging {
        resources {
            excludes += "/META-INF/{AL2.0,LGPL2.1}"
        }
    }
}

kotlin {
    compilerOptions {
        jvmTarget.set(JvmTarget.JVM_17)
    }
}

dependencies {
    implementation(libs.androidx.core.ktx)
    implementation(libs.androidx.activity.compose)

    // Jetpack Compose (versiones alineadas por el BOM)
    implementation(platform(libs.androidx.compose.bom))
    implementation(libs.androidx.compose.ui)
    implementation(libs.androidx.compose.ui.graphics)
    implementation(libs.androidx.compose.ui.tooling.preview)
    implementation(libs.androidx.compose.material3)
    implementation(libs.androidx.compose.material.icons.core)
    debugImplementation(libs.androidx.compose.ui.tooling)

    implementation(libs.androidx.navigation.compose)
    implementation(libs.androidx.lifecycle.viewmodel.compose)
    implementation(libs.androidx.lifecycle.runtime.compose)

    implementation(libs.kotlinx.coroutines.android)
    implementation(libs.kotlinx.serialization.json)

    // Red
    implementation(libs.retrofit)
    implementation(libs.retrofit.converter.kotlinx.serialization)
    implementation(libs.okhttp)
    implementation(libs.okhttp.logging.interceptor)

    // Sesión
    implementation(libs.androidx.datastore.preferences)

    // Avisos en segundo plano (sondeo periódico de la API)
    implementation(libs.androidx.work.runtime.ktx)

    testImplementation(libs.junit)
    testImplementation(libs.kotlinx.coroutines.test)
}
