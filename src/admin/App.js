import { useMemo } from '@wordpress/element';
import AdminLayout from './components/AdminLayout';
import ListingsScreen from './screens/ListingsScreen';
import SettingsScreen from './screens/SettingsScreen';
import CategoriesScreen from './screens/CategoriesScreen';
import TagsScreen from './screens/TagsScreen';

export default function App( { screen, initialTab } ) {
	const view = useMemo( () => {
		if ( screen === 'settings' ) {
			return <SettingsScreen initialTab={ initialTab } />;
		}
		if ( screen === 'categories' ) {
			return <CategoriesScreen />;
		}
		if ( screen === 'tags' ) {
			return <TagsScreen />;
		}
		return <ListingsScreen />;
	}, [ screen, initialTab ] );

	return <AdminLayout activeScreen={ screen }>{ view }</AdminLayout>;
}
