import { useMemo } from '@wordpress/element';
import AdminLayout from './components/AdminLayout';
import ListingsScreen from './screens/ListingsScreen';
import SettingsScreen from './screens/SettingsScreen';

export default function App( { screen, initialTab } ) {
	const view = useMemo( () => {
		if ( screen === 'settings' ) {
			return <SettingsScreen initialTab={ initialTab } />;
		}
		return <ListingsScreen />;
	}, [ screen, initialTab ] );

	return <AdminLayout activeScreen={ screen }>{ view }</AdminLayout>;
}
