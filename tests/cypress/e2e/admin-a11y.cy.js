describe( 'Run some accessibilty tests', function() {
    beforeEach( cy.login );

    const configureAxeWithBaselineIgnoredRuleset = () => {
        cy.configureAxe({
            rules: [
                { id: 'color-contrast', enabled: false },
                { id: 'aria-required-parent', enabled: false },
                { id: 'aria-required-children', enabled: false },
                { id: 'has-visible-text', enabled: false },
                { id: 'listitem', enabled: false },
                { id: 'link-in-text-block', enabled: false },
                { id: 'link-name', enabled: false }
            ]
        });
    };

    it('Check the form list is accessible', () => {
        cy.visit( '/wp-admin/admin.php?page=formidable' );
        cy.injectAxe();
        configureAxeWithBaselineIgnoredRuleset();
        cy.checkA11y();
    });

    it('Check the import/export page is accessible', () => {
        cy.visit( '/wp-admin/admin.php?page=formidable-import' );
        cy.injectAxe();
        configureAxeWithBaselineIgnoredRuleset();
        cy.checkA11y();
    });

});
