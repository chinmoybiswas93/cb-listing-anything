import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button, Spinner } from '@wordpress/components';
import SettingRow from '../components/SettingRow';
import AdminToastStack from '../components/AdminToastStack';
import { SettingsSidebarToggleIcon, SettingsNavIcon } from '../components/SettingsSidebarIcons';

const SIDEBAR_COLLAPSED_KEY = 'cb_listing_admin_settings_sidebar_collapsed';

const SIDEBAR = [
	{ id: 'general', label: __( 'General', 'cb-listing-anything' ) },
	{ id: 'fields', label: __( 'Fields', 'cb-listing-anything' ) },
	{ id: 'display', label: __( 'Display', 'cb-listing-anything' ) },
	{ id: 'advanced', label: __( 'Advanced', 'cb-listing-anything' ) },
];

const TOAST_MS = 5000;

export default function SettingsScreen( { initialTab } ) {
	const { namespace, settingsPageUrl } = window.cbListingAdmin;
	const [ tab, setTab ] = useState( initialTab || 'general' );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ loadError, setLoadError ] = useState( null );
	const [ payload, setPayload ] = useState( null );
	const [ toasts, setToasts ] = useState( [] );
	const [ sidebarCollapsed, setSidebarCollapsed ] = useState( () => {
		try {
			return window.localStorage.getItem( SIDEBAR_COLLAPSED_KEY ) === '1';
		} catch {
			return false;
		}
	} );

	const toastIdRef = useRef( 0 );
	const toastTimeoutsRef = useRef( new Map() );

	const removeToast = useCallback( ( id ) => {
		const t = toastTimeoutsRef.current.get( id );
		if ( t ) {
			window.clearTimeout( t );
			toastTimeoutsRef.current.delete( id );
		}
		setToasts( ( prev ) => prev.filter( ( x ) => x.id !== id ) );
	}, [] );

	const addToast = useCallback(
		( message, status = 'success' ) => {
			const id = ++toastIdRef.current;
			setToasts( ( prev ) => [ ...prev, { id, message, status } ] );
			const timer = window.setTimeout( () => {
				removeToast( id );
			}, TOAST_MS );
			toastTimeoutsRef.current.set( id, timer );
		},
		[ removeToast ]
	);

	useEffect( () => {
		return () => {
			toastTimeoutsRef.current.forEach( ( t ) => window.clearTimeout( t ) );
			toastTimeoutsRef.current.clear();
		};
	}, [] );

	useEffect( () => {
		try {
			window.localStorage.setItem( SIDEBAR_COLLAPSED_KEY, sidebarCollapsed ? '1' : '0' );
		} catch {
			// ignore
		}
	}, [ sidebarCollapsed ] );

	const load = useCallback( async () => {
		setLoading( true );
		setLoadError( null );
		try {
			const data = await apiFetch( { path: `${ namespace }/admin/settings` } );
			setPayload( data );
		} catch ( e ) {
			setLoadError( e.message || __( 'Could not load settings.', 'cb-listing-anything' ) );
		} finally {
			setLoading( false );
		}
	}, [ namespace ] );

	useEffect( () => {
		load();
	}, [ load ] );

	const settings = payload?.settings || {};
	const fieldGroups = payload?.fieldGroups || [];
	const currencies = payload?.currencies || {};
	const archiveUrl = payload?.archiveUrl || '';

	const currencyOptions = Object.keys( currencies ).map( ( code ) => ( {
		label: currencies[ code ],
		value: code,
	} ) );

	const toggleField = ( key, checked, enabledList ) => {
		const set = new Set( enabledList || [] );
		if ( checked ) {
			set.add( key );
		} else {
			set.delete( key );
		}
		return Array.from( set );
	};

	const saveGeneral = async () => {
		setSaving( true );
		try {
			const body = {
				currency: settings.currency,
				listing_title: settings.listing_title,
				listing_slug: settings.listing_slug,
			};
			const data = await apiFetch( {
				path: `${ namespace }/admin/settings`,
				method: 'PATCH',
				data: body,
			} );
			setPayload( data );
			addToast( __( 'Settings saved.', 'cb-listing-anything' ), 'success' );
		} catch ( e ) {
			addToast( e.message || __( 'Save failed.', 'cb-listing-anything' ), 'error' );
		} finally {
			setSaving( false );
		}
	};

	const saveFields = async () => {
		setSaving( true );
		try {
			const data = await apiFetch( {
				path: `${ namespace }/admin/settings`,
				method: 'PATCH',
				data: { enabled_fields: settings.enabled_fields },
			} );
			setPayload( data );
			addToast( __( 'Settings saved.', 'cb-listing-anything' ), 'success' );
		} catch ( e ) {
			addToast( e.message || __( 'Save failed.', 'cb-listing-anything' ), 'error' );
		} finally {
			setSaving( false );
		}
	};

	const sidebarHref = ( id ) => `${ settingsPageUrl }&tab=${ id }`;

	if ( loading && ! payload && ! loadError ) {
		return (
			<div className="cb-admin-settings cb-admin-settings--loading">
				<Spinner />
			</div>
		);
	}

	if ( ! loading && ! payload && loadError ) {
		return (
			<div className="cb-admin-settings cb-admin-settings--load-error">
				<p className="cb-admin-settings__load-error-text">{ loadError }</p>
				<Button variant="primary" onClick={ load }>
					{ __( 'Try again', 'cb-listing-anything' ) }
				</Button>
			</div>
		);
	}

	if ( ! payload ) {
		return (
			<div className="cb-admin-settings cb-admin-settings--loading">
				<Spinner />
			</div>
		);
	}

	return (
		<>
			<div className="cb-admin-settings">
				<div
					className={ [
						'cb-admin-settings__layout',
						sidebarCollapsed ? 'cb-admin-settings__layout--sidebar-collapsed' : '',
					]
						.filter( Boolean )
						.join( ' ' ) }
				>
					<aside
						className={ [
							'cb-admin-settings__sidebar',
							sidebarCollapsed ? 'cb-admin-settings__sidebar--collapsed' : '',
						]
							.filter( Boolean )
							.join( ' ' ) }
					>
						<div className="cb-admin-settings__sidebar-head">
							<button
								type="button"
								className="cb-admin-settings__sidebar-toggle"
								onClick={ () => setSidebarCollapsed( ( c ) => ! c ) }
								aria-expanded={ ! sidebarCollapsed }
								aria-label={
									sidebarCollapsed
										? __( 'Expand settings sidebar', 'cb-listing-anything' )
										: __( 'Collapse settings sidebar', 'cb-listing-anything' )
								}
							>
								<SettingsSidebarToggleIcon />
							</button>
							<div className="cb-admin-settings__sidebar-title-text">
								{ __( 'Settings', 'cb-listing-anything' ) }
							</div>
						</div>
						<ul className="cb-admin-settings__nav">
							{ SIDEBAR.map( ( item ) => (
								<li key={ item.id }>
									<a
										href={ sidebarHref( item.id ) }
										className={ tab === item.id ? 'is-active' : '' }
										title={ item.label }
										onClick={ ( e ) => {
											e.preventDefault();
											setTab( item.id );
											window.history.replaceState( {}, '', sidebarHref( item.id ) );
										} }
									>
										<span className="cb-admin-settings__nav-icon" aria-hidden="true">
											<SettingsNavIcon navId={ item.id } />
										</span>
										<span className="cb-admin-settings__nav-label">{ item.label }</span>
									</a>
								</li>
							) ) }
						</ul>
					</aside>
					<main className="cb-admin-settings__main">
						{ tab === 'general' && (
							<div className="cb-admin-card cb-admin-card--modern">
								<div className="cb-admin-card__header">
									<h2>{ __( 'General settings', 'cb-listing-anything' ) }</h2>
									<Button
										variant="primary"
										className="cb-admin-save-btn"
										onClick={ saveGeneral }
										isBusy={ saving }
										disabled={ saving }
									>
										{ __( 'Save settings', 'cb-listing-anything' ) }
									</Button>
								</div>
								<div className="cb-admin-card__canvas">
									<div className="cb-admin-card__body cb-admin-card__body--rows">
										<SettingRow
											title={ __( 'Currency', 'cb-listing-anything' ) }
											description={ __(
												'Select the currency to display with listing prices.',
												'cb-listing-anything'
											) }
										>
											<select
												className="cb-admin-select"
												value={ settings.currency || 'USD' }
												onChange={ ( e ) =>
													setPayload( {
														...payload,
														settings: { ...settings, currency: e.target.value },
													} )
												}
											>
												{ currencyOptions.map( ( opt ) => (
													<option key={ opt.value } value={ opt.value }>
														{ opt.label }
													</option>
												) ) }
											</select>
										</SettingRow>
										<SettingRow
											title={ __( 'Listing title', 'cb-listing-anything' ) }
											description={ __(
												'Label used in the admin (e.g. “Listing”, “Property”).',
												'cb-listing-anything'
											) }
										>
											<input
												type="text"
												className="cb-admin-input"
												value={ settings.listing_title || '' }
												onChange={ ( e ) =>
													setPayload( {
														...payload,
														settings: { ...settings, listing_title: e.target.value },
													} )
												}
											/>
										</SettingRow>
										<SettingRow
											title={ __( 'Listing slug', 'cb-listing-anything' ) }
											description={ __(
												'URL slug for the listing archive. Must be unique. Permalinks need to be flushed manually after change (Settings → Permalinks → Save).',
												'cb-listing-anything'
											) }
										>
											<input
												type="text"
												className="cb-admin-input"
												value={ settings.listing_slug || '' }
												onChange={ ( e ) =>
													setPayload( {
														...payload,
														settings: { ...settings, listing_slug: e.target.value },
													} )
												}
											/>
										</SettingRow>
										<SettingRow
											title={ __( 'Archive URL', 'cb-listing-anything' ) }
											description={ __(
												'Public URL for the listing archive (opens in a new tab).',
												'cb-listing-anything'
											) }
										>
											<a
												className="cb-admin-archive-link"
												href={ archiveUrl }
												target="_blank"
												rel="noreferrer"
											>
												{ archiveUrl }
											</a>
										</SettingRow>
									</div>
								</div>
							</div>
						) }

						{ tab === 'fields' && (
							<div className="cb-admin-card cb-admin-card--modern">
								<div className="cb-admin-card__header">
									<h2>{ __( 'Listing fields', 'cb-listing-anything' ) }</h2>
									<Button
										variant="primary"
										className="cb-admin-save-btn"
										onClick={ saveFields }
										isBusy={ saving }
										disabled={ saving }
									>
										{ __( 'Save settings', 'cb-listing-anything' ) }
									</Button>
								</div>
								<div className="cb-admin-card__canvas">
									<div className="cb-admin-card__body cb-admin-card__body--fields">
										{ fieldGroups.map( ( group ) => (
											<section key={ group.id } className="cb-admin-field-section">
												<div className="cb-admin-field-section__head">
													<h3 className="cb-admin-field-section__title">{ group.label }</h3>
												</div>
												<div className="cb-admin-field-section__rows">
													{ group.fields.map( ( f ) => {
														const enabled = ( settings.enabled_fields || [] ).includes( f.key );
														return (
															<div key={ f.key } className="cb-admin-setting-row cb-admin-setting-row--toggle">
																<div className="cb-admin-setting-row__main">
																	<div className="cb-admin-setting-row__title">{ f.label }</div>
																	<p className="cb-admin-setting-row__desc">{ f.typeLabel }</p>
																</div>
																<div className="cb-admin-setting-row__control">
																	<label className="cb-admin-switch">
																		<input
																			type="checkbox"
																			checked={ enabled }
																			onChange={ ( e ) =>
																				setPayload( {
																					...payload,
																					settings: {
																						...settings,
																						enabled_fields: toggleField(
																							f.key,
																							e.target.checked,
																							settings.enabled_fields
																						),
																					},
																				} )
																			}
																		/>
																		<span className="cb-admin-switch__track" aria-hidden="true" />
																	</label>
																</div>
															</div>
														);
													} ) }
												</div>
											</section>
										) ) }
									</div>
								</div>
							</div>
						) }

						{ tab === 'display' && (
							<div className="cb-admin-card cb-admin-card--modern">
								<div className="cb-admin-card__header">
									<h2>{ __( 'Display settings', 'cb-listing-anything' ) }</h2>
								</div>
								<div className="cb-admin-card__canvas">
									<div className="cb-admin-card__body cb-admin-card__body--rows">
										<SettingRow
											title={ __( 'Coming soon', 'cb-listing-anything' ) }
											description={ __(
												'Display settings will be available in a future update.',
												'cb-listing-anything'
											) }
										>
											<span className="cb-admin-placeholder-pill">{ __( 'Planned', 'cb-listing-anything' ) }</span>
										</SettingRow>
									</div>
								</div>
							</div>
						) }

						{ tab === 'advanced' && (
							<div className="cb-admin-card cb-admin-card--modern">
								<div className="cb-admin-card__header">
									<h2>{ __( 'Advanced settings', 'cb-listing-anything' ) }</h2>
								</div>
								<div className="cb-admin-card__canvas">
									<div className="cb-admin-card__body cb-admin-card__body--rows">
										<SettingRow
											title={ __( 'Coming soon', 'cb-listing-anything' ) }
											description={ __(
												'Advanced settings will be available in a future update.',
												'cb-listing-anything'
											) }
										>
											<span className="cb-admin-placeholder-pill">{ __( 'Planned', 'cb-listing-anything' ) }</span>
										</SettingRow>
									</div>
								</div>
							</div>
						) }
					</main>
				</div>
			</div>
			<AdminToastStack toasts={ toasts } onDismiss={ removeToast } />
		</>
	);
}
