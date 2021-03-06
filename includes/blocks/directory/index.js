/**
 * WordPress dependencies
 */
const { __, _n, _nx, _x } = wp.i18n;
// const { select } = wp.data;
const { registerBlockType } = wp.blocks;
const {
	      InspectorControls,
	      InspectorAdvancedControls,
      } = wp.blockEditor;
const {
	      // ServerSideRender,
	      PanelBody,
	      // CheckboxControl,
	      SelectControl,
	      TextControl,
	      ToggleControl
      } = wp.components;
const {
	      // Component,
	      Fragment,
      } = wp.element;

const { serverSideRender: ServerSideRender, } = wp;

/**
 * Internal dependencies
 */
import {
	FilterTagSelector,
	HierarchicalTermSelector,
	PageSelect
} from '@Connections-Directory/components';

// Import CSS
// import './styles/editor.scss';
// import './styles/public.scss';

const {
	      entryTypes,
	      dateTypes,
	      templates
      } = cbDir.blockSettings;

/**
 * Register Block
 */
export default registerBlockType(
	'connections-directory/shortcode-connections',
	{
		title:       __( 'Directory', 'connections' ),
		description: __( 'Display the Connections Business Directory.', 'connections' ),
		category:    'connections-directory',
		// icon:        giveLogo,
		keywords:    [
			'connections',
			__( 'directory', 'connections' ),
		],
		supports:    {
			// Remove the support for the generated className.
			className:       false,
			// Remove the support for the custom className.
			customClassName: false,
			// Remove the support for editing the block using the block HTML editor.
			html:            false,
		},
		attributes:  {
			// Valid attribute types are: string, boolean, object, null, array, integer, number
			// @see link https://github.com/WordPress/gutenberg/blob/master/packages/blocks/src/api/parser.js
			advancedBlockOptions: {
				type:    'string',
				default: '',
			},
			categories:           {
				type:    'string',
				default: '[]',
			},
			characterIndex:       {
				type:    'boolean',
				default: true,
			},
			city:                 {
				type:    'array',
				default: [],
			},
			county:               {
				type:    'array',
				default: [],
			},
			country:              {
				type:    'array',
				default: [],
			},
			department:           {
				type:    'array',
				default: [],
			},
			district:             {
				type:    'array',
				default: [],
			},
			excludeCategories:    {
				type:    'string',
				default: '[]',
			},
			forceHome:            {
				type:    'boolean',
				default: false,
			},
			fullName:             {
				type:    'array',
				default: [],
			},
			homePage:             {
				type:    'string',
				default: ''
			},
			inCategories:         {
				type:    'boolean',
				default: false,
			},
			isEditorPreview:      {
				type:    'boolean',
				default: true,
			},
			lastName:             {
				type:    'array',
				default: [],
			},
			listType:             {
				type:    'string',
				default: 'all',
			},
			order:                {
				type:    'string',
				default: 'asc',
			},
			orderBy:              {
				type:    'string',
				default: 'default',
			},
			orderRandom:          {
				type:    'boolean',
				default: false,
			},
			organization:         {
				type:    'array',
				default: [],
			},
			parseQuery:           {
				type:    'boolean',
				default: true
			},
			repeatCharacterIndex: {
				type:    'boolean',
				default: false,
			},
			sectionHead:          {
				type:    'boolean',
				default: false,
			},
			state:                {
				type:    'array',
				default: [],
			},
			template:             {
				type:    'string',
				default: templates.active
			},
			title:                {
				type:    'array',
				default: [],
			},
			zipcode:              {
				type:    'array',
				default: [],
			},
		},
		edit:        function( { attributes, setAttributes } ) {

			const {
				      advancedBlockOptions,
				      categories,
				      characterIndex,
				      city,
				      county,
				      country,
				      department,
				      district,
				      excludeCategories,
				      forceHome,
				      fullName,
				      homePage,
				      inCategories,
				      lastName,
				      listType,
				      order,
				      orderBy,
				      orderRandom,
				      organization,
				      parseQuery,
				      repeatCharacterIndex,
				      sectionHead,
				      state,
				      template,
				      title,
				      zipcode,
			      } = attributes;

			// const { getCurrentPostId } = select( 'core/editor' );
			// const postId               = getCurrentPostId();

			const templateOptions = [];
			const entryTypeSelectOptions = [];
			const dateTypeSelectOptions = [];

			for ( let property in templates.registered ) {

				// noinspection JSUnfilteredForInLoop
				templateOptions.push( {
					label: templates.registered[ property ],
					value: property
				} )
			}

			for ( let property in entryTypes ) {

				// noinspection JSUnfilteredForInLoop
				entryTypeSelectOptions.push( {
					label: entryTypes[ property ],
					value: property
				} )
			}

			for ( let property in dateTypes ) {

				// noinspection JSUnfilteredForInLoop
				dateTypeSelectOptions.push( {
					label: __( 'Date:', 'connections' ) + ' ' + dateTypes[ property ],
					value: property
				} )
			}

			return (
				<Fragment>
					<InspectorControls>
						<PanelBody
							title={__( 'Character Index', 'connections' )}
							initialOpen={false}
						>

							<ToggleControl
								label={__( 'Display Character Index?', 'connections' )}
								help={__( 'Display the A-Z index above the directory.', 'connections' )}
								checked={!!characterIndex}
								onChange={() => setAttributes( { characterIndex: !characterIndex } )}
							/>

							<ToggleControl
								label={__( 'Repeat Character Index?', 'connections' )}
								help={__( 'Repeat the Character Index at the beginning of each character group.', 'connections' )}
								checked={!!repeatCharacterIndex}
								onChange={() => setAttributes( { repeatCharacterIndex: !repeatCharacterIndex } )}
							/>

							<ToggleControl
								label={__( 'Display Current Character Heading?', 'connections' )}
								help={__( 'Display the current character heading at the beginning of each character group.', 'connections' )}
								checked={!!sectionHead}
								onChange={() => setAttributes( { sectionHead: !sectionHead } )}
							/>

						</PanelBody>

						<PanelBody
							title={__( 'Template', 'connections' )}
							initialOpen={false}
						>
							<SelectControl
								label={__( 'Template', 'connections' )}
								help={__( 'Select which to use when displaying the directory.', 'connections' )}
								value={template}
								options={templateOptions}
								onChange={( template ) => setAttributes( { template: template } )}
							/>
						</PanelBody>

						<PanelBody
							title={__( 'Select', 'connections' )}
							initialOpen={true}
						>
							<p>
								{__( 'This section controls which entries from your directory will be displayed.', 'connections' )}
							</p>

							<div style={{ marginTop: '20px' }}>
								<SelectControl
									label={__( 'Entry Type', 'connections' )}
									help={__( 'Select which entry type to display. The default is to display all.', 'connections' )}
									value={listType}
									options={[
										{ label: __( 'All', 'connections' ), value: 'all' },
										...entryTypeSelectOptions
									]}
									onChange={( listType ) => setAttributes( { listType: listType } )}
								/>
							</div>

							<div style={{ marginTop: '20px' }}>
								<p>
									{__( 'Choose the categories to include in the entry list.', 'connections' )}
								</p>
							</div>

							<HierarchicalTermSelector
								taxonomy='category'
								terms={JSON.parse( categories )}
								onChange={( categories ) => setAttributes( { categories: JSON.stringify( categories ) } )}
							/>

							<div style={{ marginTop: '20px' }}>
								<ToggleControl
									label={__( 'Entries must be assigned to all the above chosen categories?', 'connections' )}
									// help={__( '', 'connections' )}
									checked={!!inCategories}
									onChange={() => setAttributes( { inCategories: !inCategories } )}
								/>
							</div>

							<div style={{ marginTop: '20px' }}>
								<p>
									{__( 'Choose the categories to exclude from the entry list.', 'connections' )}
								</p>
							</div>

							<HierarchicalTermSelector
								taxonomy='category'
								terms={JSON.parse( excludeCategories )}
								onChange={( excludeCategories ) => setAttributes( { excludeCategories: JSON.stringify( excludeCategories ) } )}
							/>

						</PanelBody>

						<PanelBody
							title={__( 'Filters', 'connections' )}
							initialOpen={false}
						>

							<div className={'components-base-control'} style={{ marginTop: '20px' }}>
								<p>
									{
										__(
											'Only the entries which match all of the selected filters below will be displayed in the results list.',
											'connections'
										)
									}
								</p>
								<p className={'components-base-control__help'} style={{ marginTop: '4px' }}>
									{
										__(
											'Start typing for suggestions. You must choose a suggested term for the filter to be applied. More than one term can be entered per filter. If more than one is entered, as long as the entry has a term in any of the inputs, it will display as a result.',
											'connections'
										)
									}
								</p>
							</div>

							<div style={{ marginTop: '20px' }}>
								<FilterTagSelector
									type={'name'}        // The autocomplete type.
									label={__( 'Full Name', 'connections' )}
									renderField={'name'} // The field to display as tags / in autocomplete list.
									getFields={'id,name'}   // The fields to return from the REST query.
									returnField={'id'} // This is the field to return and saved.
									messages={{
										added:   __( 'Full Name Added to Filter', 'connections' ),
										removed: __( 'Full Name Removed', 'connections' ),
										remove:  __( 'Remove Full Name from Filter', 'connections' ),
									}}
									terms={fullName}
									onChange={( value ) => setAttributes( { fullName: value } )}
								/>
							</div>

							<div style={{ marginTop: '20px' }}>
								<FilterTagSelector
									type={'last_name'}        // The autocomplete type.
									label={__( 'Last Name', 'connections' )}
									renderField={'last_name'} // The field to display as tags / in autocomplete list.
									getFields={'last_name'}   // The fields to return from the REST query.
									returnField={'last_name'} // This is the field to return and saved.
									messages={{
										added:   __( 'Last Name Added to Filter', 'connections' ),
										removed: __( 'Last Name Removed', 'connections' ),
										remove:  __( 'Remove Last Name from Filter', 'connections' ),
									}}
									terms={lastName}
									onChange={( value ) => setAttributes( { lastName: value } )}
								/>
							</div>

							<div style={{ marginTop: '20px' }}>
								<FilterTagSelector
									type={'title'}        // The autocomplete type.
									label={__( 'Title', 'connections' )}
									renderField={'title'} // The field to display as tags / in autocomplete list.
									getFields={'title'}   // The fields to return from the REST query.
									returnField={'title'} // This is the field to return and saved.
									messages={{
										added:   __( 'Title Added to Filter', 'connections' ),
										removed: __( 'Title Removed', 'connections' ),
										remove:  __( 'Remove Title from Filter', 'connections' ),
									}}
									terms={title}
									onChange={( value ) => setAttributes( { title: value } )}
								/>
							</div>

							<div className={'components-base-control'} style={{ marginTop: '20px' }}>
								<FilterTagSelector
									type={'department'}        // The autocomplete type.
									label={__( 'Department', 'connections' )}
									renderField={'department'} // The field to display as tags / in autocomplete list.
									getFields={'department'}   // The fields to return from the REST query.
									returnField={'department'} // This is the field to return and saved.
									messages={{
										added:   __( 'Department Added to Filter', 'connections' ),
										removed: __( 'Department Removed', 'connections' ),
										remove:  __( 'Remove Department from Filter', 'connections' ),
									}}
									terms={department}
									onChange={( value ) => setAttributes( { department: value } )}
								/>
							</div>

							<div className={'components-base-control'} style={{ marginTop: '20px' }}>
								<FilterTagSelector
									type={'organization'}        // The autocomplete type.
									label={__( 'Organization', 'connections' )}
									renderField={'organization'} // The field to display as tags / in autocomplete list.
									getFields={'organization'}   // The fields to return from the REST query.
									returnField={'organization'} // This is the field to return and saved.
									messages={{
										added:   __( 'Organization Added to Filter', 'connections' ),
										removed: __( 'Organization Removed', 'connections' ),
										remove:  __( 'Remove Organization from Filter', 'connections' ),
									}}
									terms={organization}
									onChange={( value ) => setAttributes( { organization: value } )}
								/>
							</div>

							<div className={'components-base-control'} style={{ marginTop: '20px' }}>
								<FilterTagSelector
									type={'district'}        // The autocomplete type.
									label={__( 'District', 'connections' )}
									renderField={'district'} // The field to display as tags / in autocomplete list.
									getFields={'district'}   // The fields to return from the REST query.
									returnField={'district'} // This is the field to return and saved.
									messages={{
										added:   __( 'District Added to Filter', 'connections' ),
										removed: __( 'District Removed', 'connections' ),
										remove:  __( 'Remove District from Filter', 'connections' ),
									}}
									terms={district}
									onChange={( value ) => setAttributes( { district: value } )}
								/>
							</div>

							<div className={'components-base-control'} style={{ marginTop: '20px' }}>
								<FilterTagSelector
									type={'county'}        // The autocomplete type.
									label={__( 'County', 'connections' )}
									renderField={'county'} // The field to display as tags / in autocomplete list.
									getFields={'county'}   // The fields to return from the REST query.
									returnField={'county'} // This is the field to return and saved.
									messages={{
										added:   __( 'County Added to Filter', 'connections' ),
										removed: __( 'County Removed', 'connections' ),
										remove:  __( 'Remove County from Filter', 'connections' ),
									}}
									terms={county}
									onChange={( value ) => setAttributes( { county: value } )}
								/>
							</div>

							<div className={'components-base-control'} style={{ marginTop: '20px' }}>
								<FilterTagSelector
									type={'city'}        // The autocomplete type.
									label={__( 'City', 'connections' )}
									renderField={'city'} // The field to display as tags / in autocomplete list.
									getFields={'city'}   // The fields to return from the REST query.
									returnField={'city'} // This is the field to return and saved.
									messages={{
										added:   __( 'City Added to Filter', 'connections' ),
										removed: __( 'City Removed', 'connections' ),
										remove:  __( 'Remove City from Filter', 'connections' ),
									}}
									terms={city}
									onChange={( value ) => setAttributes( { city: value } )}
								/>
							</div>

							<div className={'components-base-control'} style={{ marginTop: '20px' }}>
								<FilterTagSelector
									type={'state'}        // The autocomplete type.
									label={__( 'State', 'connections' )}
									renderField={'state'} // The field to display as tags / in autocomplete list.
									getFields={'state'}   // The fields to return from the REST query.
									returnField={'state'} // This is the field to return and saved.
									messages={{
										added:   __( 'State Added to Filter', 'connections' ),
										removed: __( 'State Removed', 'connections' ),
										remove:  __( 'Remove State from Filter', 'connections' ),
									}}
									terms={state}
									onChange={( value ) => setAttributes( { state: value } )}
								/>
							</div>

							<div className={'components-base-control'} style={{ marginTop: '20px' }}>
								<FilterTagSelector
									type={'zipcode'}        // The autocomplete type.
									label={__( 'Zipcode', 'connections' )}
									renderField={'zipcode'} // The field to display as tags / in autocomplete list.
									getFields={'zipcode'}   // The fields to return from the REST query.
									returnField={'zipcode'} // This is the field to return and saved.
									messages={{
										added:   __( 'Zipcode Added to Filter', 'connections' ),
										removed: __( 'Zipcode Removed', 'connections' ),
										remove:  __( 'Remove Zipcode from Filter', 'connections' ),
									}}
									terms={zipcode}
									onChange={( value ) => setAttributes( { zipcode: value } )}
								/>
							</div>

							<div className={'components-base-control'} style={{ marginTop: '20px' }}>
								<FilterTagSelector
									type={'country'}        // The autocomplete type.
									label={__( 'Country', 'connections' )}
									renderField={'country'} // The field to display as tags / in autocomplete list.
									getFields={'country'}   // The fields to return from the REST query.
									returnField={'country'} // This is the field to return and saved.
									messages={{
										added:   __( 'Country Added to Filter', 'connections' ),
										removed: __( 'Country Removed', 'connections' ),
										remove:  __( 'Remove Country from Filter', 'connections' ),
									}}
									terms={country}
									onChange={( value ) => setAttributes( { country: value } )}
								/>
							</div>

						</PanelBody>

						<PanelBody
							title={__( 'Order', 'connections' )}
							initialOpen={false}
						>
							<p>
								{__( 'This section controls the order in which the selected entries will be displayed.', 'connections' )}
							</p>

							<SelectControl
								label={__( 'Order By', 'connections' )}
								value={orderBy}
								options={[
									{ label: __( 'Default', 'connections' ), value: 'default' },
									{ label: __( 'First Name', 'connections' ), value: 'first_name' },
									{ label: __( 'Last Name', 'connections' ), value: 'last_name' },
									{ label: __( 'Title', 'connections' ), value: 'title' },
									{ label: __( 'Organization', 'connections' ), value: 'organization' },
									{ label: __( 'Department', 'connections' ), value: 'department' },
									{ label: __( 'City', 'connections' ), value: 'city' },
									{ label: __( 'State', 'connections' ), value: 'state' },
									{ label: __( 'Zipcode', 'connections' ), value: 'zipcode' },
									{ label: __( 'Country', 'connections' ), value: 'country' },
									{ label: __( 'Date: Entry Added', 'connections' ), value: 'date_added' },
									{ label: __( 'Date: Entry Last Modified', 'connections' ), value: 'date_modified' },
									...dateTypeSelectOptions
								]}
								onChange={( orderBy ) => setAttributes( { orderBy: orderBy } )}
								disabled={!!orderRandom}
							/>

							<SelectControl
								label={__( 'Order', 'connections' )}
								value={order}
								options={[
									{ label: __( 'Ascending', 'connections' ), value: 'asc' },
									{ label: __( 'Descending', 'connections' ), value: 'desc' },
									{ label: __( 'Random', 'connections' ), value: 'random' },
								]}
								onChange={( order ) => setAttributes( {
									order:       order,
									orderBy:     'random' === order ? 'default' : orderBy,
									orderRandom: 'random' === order
								} )}
							/>

						</PanelBody>

					</InspectorControls>
					<InspectorAdvancedControls>

						<p>
							{__( 'This section controls advanced options which effect the directory features and functions.', 'connections' )}
						</p>

						<ToggleControl
							label={__( 'Parse query?', 'connections' )}
							help={__( 'Permit the Directory block instance to parse queries in order to affect the displayed results. Example, allowing keyword searches. The default is to allow query parsing.', 'connections' )}
							checked={!!parseQuery}
							onChange={() => setAttributes( { parseQuery: !parseQuery } )}
						/>

						<PageSelect
							// postType={'post'}
							label={__( 'Directory Home Page', 'connections' )}
							noOptionLabel={__( 'Current Page', 'connections' )}
							value={homePage}
							onChange={( homePage ) => setAttributes( { homePage: homePage } )}
							disabled={!!forceHome}
						/>

						<ToggleControl
							label={__( 'Force directory permalinks to resolve to the Global Directory Homes page?', 'connections' )}
							checked={!!forceHome}
							onChange={() => setAttributes( {
								forceHome: !forceHome,
								homePage:  '',
							} )}
						/>

						<TextControl
							label={__( 'Additional Options', 'connections' )}
							value={advancedBlockOptions}
							onChange={( newValue ) => {
								setAttributes( {
									advancedBlockOptions: newValue,
								} );
							}}
						/>

					</InspectorAdvancedControls>
					<ServerSideRender
						attributes={attributes}
						block='connections-directory/shortcode-connections'
					/>
				</Fragment>
			);

		},
		save:        function() {
			// Server side rendering via shortcode.
			return null;
		},
	}
);
