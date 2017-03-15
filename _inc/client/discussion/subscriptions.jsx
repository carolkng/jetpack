/**
 * External dependencies
 */
import React from 'react';
import { translate as __ } from 'i18n-calypso';
import CompactFormToggle from 'components/form/form-toggle/compact';
import Card from 'components/card';

/**
 * Internal dependencies
 */
import { FormFieldset } from 'components/forms';
import { ModuleToggle } from 'components/module-toggle';
import { ModuleSettingsForm as moduleSettingsForm } from 'components/module-settings/module-settings-form';
import SettingsCard from 'components/settings-card';
import SettingsGroup from 'components/settings-group';

export const Subscriptions = moduleSettingsForm(
	React.createClass( {

		/**
		 * Get options for initial state.
		 *
		 * @returns {{stb_enabled: *, stc_enabled: *}}
		 */
		getInitialState() {
			return {
				stb_enabled: this.props.getOptionValue( 'stb_enabled', 'subscriptions' ),
				stc_enabled: this.props.getOptionValue( 'stc_enabled', 'subscriptions' )
			};
		},

		/**
		 * Update state so toggles are updated.
		 *
		 * @param {string} optionName
		 */
		updateOptions( optionName ) {
			this.setState(
				{
					[ optionName ]: ! this.state[ optionName ]
				},
				this.props.updateFormStateModuleOption( 'subscriptions', optionName )
			);
		},

		render() {
			let subscriptions = this.props.getModule( 'subscriptions' ),
				isSubscriptionsActive = this.props.getOptionValue( 'subscriptions' ),
				unavailableInDevMode = this.props.isUnavailableInDevMode( 'subscriptions' );
			return (
				<SettingsCard
					{ ...this.props }
					hideButton
					module="subscriptions">
					<SettingsGroup hasChild disableInDevMode module={ subscriptions }>
						<ModuleToggle
							slug="subscriptions"
							disabled={ unavailableInDevMode }
							activated={ isSubscriptionsActive }
							toggling={ this.props.isSavingAnyOption( 'subscriptions' ) }
							toggleModule={ this.props.toggleModuleNow }>
						<span className="jp-form-toggle-explanation">
							{
								subscriptions.description
							}
						</span>
						</ModuleToggle>
						{
							<FormFieldset>
								<CompactFormToggle
									checked={ this.state.stb_enabled }
									disabled={ ! isSubscriptionsActive || unavailableInDevMode || this.props.isSavingAnyOption() }
									onChange={ () => this.updateOptions( 'stb_enabled' ) }>
									<span className="jp-form-toggle-explanation">
										{
											__( 'Show a "follow blog" option in the comment form' )
										}
									</span>
								</CompactFormToggle>
								<CompactFormToggle
									checked={ this.state.stc_enabled }
									disabled={ ! isSubscriptionsActive || unavailableInDevMode || this.props.isSavingAnyOption() }
									onChange={ () => this.updateOptions( 'stc_enabled' ) }>
									<span className="jp-form-toggle-explanation">
										{
											__( 'Show a "follow comments" option in the comment form' )
										}
									</span>
								</CompactFormToggle>
							</FormFieldset>
						}
					</SettingsGroup>
					{
						! unavailableInDevMode && isSubscriptionsActive && (
							<Card compact className="jp-settings-card__configure-link" href={ 'https://wordpress.com/people/email-followers/' + this.props.siteRawUrl }>{ __( 'View your Email Followers' ) }</Card>
						)
					}
				</SettingsCard>
			);
		}
	} )
);
