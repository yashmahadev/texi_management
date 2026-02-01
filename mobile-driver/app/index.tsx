import { Redirect } from 'expo-router';
import { useAuthStore } from '../store/authStore';

export default function Index() {
    const { isAuthenticated } = useAuthStore();

    // If authenticated, go to tabs, else login
    // Note: RootLayout also handles this, but having an index file ensures we have a valid entry point.
    return <Redirect href={isAuthenticated ? "/(tabs)" : "/login"} />;
}
