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

android {
    namespace = "com.codeflow.app"
    compileSdk = 35

    defaultConfig {
        applicationId = "com.codeflow.app"
        minSdk = 26
        targetSdk = 35
        versionCode = 1
        versionName = "0.1.0"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
    }

    buildTypes {
        debug {
            // Emulador de Android Studio → 10.0.2.2 es el "localhost" del PC (Docker en :8080)
            buildConfigField("String", "BASE_URL", "\"$debugApiUrl\"")
        }
        release {
            // TODO(fase 15-16): sustituir por la URL HTTPS real del servidor de producción.
            buildConfigField("String", "BASE_URL", "\"https://codeflow.example.com/api/\"")
            isMinifyEnabled = false
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro",
            )
            // La firma se configura desde Android Studio (Build > Generate Signed Bundle/APK).
            // El keystore NUNCA se sube al repositorio.
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

    // TODO(fase 19): Firebase Cloud Messaging (plugin google-services + firebase-messaging)
    //  para recibir las notificaciones push. No se añade todavía.

    testImplementation(libs.junit)
    testImplementation(libs.kotlinx.coroutines.test)
}
