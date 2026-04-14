/**
 * JS модуля "Календарний план змагань ФСТУ".
 *
 * Version: 1.6.0
 * Date_update: 2026-04-13
 *
 * @package FSTU
 */

/* global fstuCalendarL10n */

jQuery( document ).ready( function ( $ ) {
	'use strict';

	if ( typeof fstuCalendarL10n === 'undefined' ) {
		return;
	}

	const $module = $( '#fstu-calendar' );
	if ( ! $module.length ) {
		return;
	}

	const permissions = fstuCalendarL10n.permissions || {};
	const eventPermissions = permissions.events || {};
	const applicationsPermissions = permissions.applications || {};
	const resultsPermissions = permissions.results || {};
	const datasets = fstuCalendarL10n.datasets || {};
	const applicationStatusFlow = getApplicationStatusFlowMap();
	const state = {
		list: {
			page: 1,
			perPage: 10,
			search: '',
			year: parseInt( fstuCalendarL10n.currentYear, 10 ) || new Date().getFullYear(),
			statusId: 0,
			regionId: 0,
			tourismTypeId: 0,
			eventTypeId: 0,
			total: 0,
			totalPages: 1
		},
		protocol: {
			page: 1,
			perPage: 10,
			search: '',
			total: 0,
			totalPages: 1
		},
		applications: {
			page: 1,
			perPage: 10,
			search: '',
			total: 0,
			totalPages: 1,
			selectedEventId: 0,
			selectedEventName: '',
			currentItem: null
		},
		applicationsProtocol: {
			page: 1,
			perPage: 10,
			search: '',
			total: 0,
			totalPages: 1
		},
		results: {
			page: 1,
			perPage: 10,
			search: '',
			total: 0,
			totalPages: 1,
			selectedEventId: 0,
			selectedEventName: '',
			selectedRaceId: 0,
			protocolItems: [],
			protocolEditing: false
		},
		resultsProtocol: {
			page: 1,
			perPage: 10,
			search: '',
			total: 0,
			totalPages: 1
		},
		calendar: {
			mode: 'month',
			year: parseInt( fstuCalendarL10n.currentYear, 10 ) || new Date().getFullYear(),
			month: parseInt( fstuCalendarL10n.currentMonth, 10 ) || ( new Date().getMonth() + 1 ),
			weekStart: getMonday( new Date() )
		}
	};

	let dropdownState = null;

	bindShellTabs();
	bindFilters();
	bindPagination();
	bindApplications();
	bindResults();
	bindProtocol();
	bindViewNavigation();
	bindModals();
	bindActions();

	loadEvents();
	loadCalendarView();
	initializeApplicationActionsAvailability();
	initializeResultsActionsAvailability();

	function bindShellTabs() {
		$( document ).on( 'click', '.fstu-shell-tab', function () {
			const target = $( this ).data( 'target' );
			$( '.fstu-shell-tab' ).removeClass( 'is-active' );
			$( this ).addClass( 'is-active' );
			$( '.fstu-shell-panel' ).addClass( 'fstu-hidden' ).removeClass( 'is-active' );
			$( '[data-panel="' + target + '"]' ).removeClass( 'fstu-hidden' ).addClass( 'is-active' );

			if ( target === 'applications' ) {
				resetApplicationsProtocolView();
				if ( state.applications.selectedEventId > 0 ) {
					loadApplications();
				} else {
					renderApplicationsEmpty( 'Оберіть захід у реєстрі, щоб переглянути заявки.' );
				}
			}

			if ( target === 'results' ) {
				if ( state.results.selectedEventId > 0 ) {
					loadResults();
				} else {
					renderResultsEmpty( 'Оберіть захід у реєстрі, щоб переглянути перегони.' );
				}
			}
		} );

		$( document ).on( 'click', '#fstu-calendar-protocol-btn', function () {
			$( '#fstu-calendar-main, #fstu-calendar-view-panel, .fstu-shell-panel[data-panel="applications"], .fstu-shell-panel[data-panel="routes"], .fstu-shell-panel[data-panel="results"]' ).addClass( 'fstu-hidden' );
			$( '#fstu-calendar-protocol' ).removeClass( 'fstu-hidden' );
			$( '#fstu-calendar-protocol-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-calendar-protocol-back-btn' ).removeClass( 'fstu-hidden' );
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-calendar-protocol-back-btn', function () {
			$( '#fstu-calendar-main' ).removeClass( 'fstu-hidden' );
			$( '#fstu-calendar-protocol' ).addClass( 'fstu-hidden' );
			$( '#fstu-calendar-protocol-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-calendar-protocol-back-btn' ).addClass( 'fstu-hidden' );
		} );
	}

	function bindFilters() {
		$( document ).on( 'input', '#fstu-calendar-search', debounce( function () {
			state.list.search = $( this ).val().trim();
			state.list.page = 1;
			loadEvents();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-calendar-year, #fstu-calendar-status, #fstu-calendar-region, #fstu-calendar-tourism-type, #fstu-calendar-event-type', function () {
			state.list.year = parseInt( $( '#fstu-calendar-year' ).val(), 10 ) || state.list.year;
			state.list.statusId = parseInt( $( '#fstu-calendar-status' ).val(), 10 ) || 0;
			state.list.regionId = parseInt( $( '#fstu-calendar-region' ).val(), 10 ) || 0;
			state.list.tourismTypeId = parseInt( $( '#fstu-calendar-tourism-type' ).val(), 10 ) || 0;
			state.list.eventTypeId = parseInt( $( '#fstu-calendar-event-type' ).val(), 10 ) || 0;
			state.list.page = 1;
			state.calendar.year = state.list.year;
			loadEvents();
			loadCalendarView();
		} );

		$( document ).on( 'change', '#fstu-calendar-per-page', function () {
			state.list.perPage = parseInt( $( this ).val(), 10 ) || 10;
			state.list.page = 1;
			loadEvents();
		} );

		$( document ).on( 'click', '#fstu-calendar-refresh-btn', function () {
			loadEvents();
			loadCalendarView();
		} );
	}

	function bindPagination() {
		$( document ).on( 'click', '#fstu-calendar-prev-page', function () {
			if ( state.list.page > 1 ) {
				state.list.page--;
				loadEvents();
			}
		} );

		$( document ).on( 'click', '#fstu-calendar-next-page', function () {
			if ( state.list.page < state.list.totalPages ) {
				state.list.page++;
				loadEvents();
			}
		} );

		$( document ).on( 'click', '.fstu-calendar-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			if ( page !== state.list.page ) {
				state.list.page = page;
				loadEvents();
			}
		} );
	}

	function bindApplications() {
		$( document ).on( 'input', '#fstu-calendar-applications-search', debounce( function () {
			state.applications.search = $( this ).val().trim();
			state.applications.page = 1;
			loadApplications();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-calendar-applications-per-page', function () {
			state.applications.perPage = parseInt( $( this ).val(), 10 ) || 10;
			state.applications.page = 1;
			loadApplications();
		} );

		$( document ).on( 'click', '#fstu-calendar-applications-prev-page', function () {
			if ( state.applications.page > 1 ) {
				state.applications.page--;
				loadApplications();
			}
		} );

		$( document ).on( 'click', '#fstu-calendar-applications-next-page', function () {
			if ( state.applications.page < state.applications.totalPages ) {
				state.applications.page++;
				loadApplications();
			}
		} );

		$( document ).on( 'click', '.fstu-calendar-applications-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			if ( page !== state.applications.page ) {
				state.applications.page = page;
				loadApplications();
			}
		} );

		$( document ).on( 'click', '#fstu-calendar-applications-back-to-events', function () {
			resetApplicationsProtocolView();
			activateShellTab( 'registry' );
		} );

		$( document ).on( 'click', '#fstu-calendar-applications-protocol-btn', function () {
			$( '#fstu-calendar-applications-main-content' ).addClass( 'fstu-hidden' );
			$( '#fstu-calendar-applications-protocol' ).removeClass( 'fstu-hidden' );
			$( '#fstu-calendar-applications-protocol-btn, #fstu-calendar-add-application-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-calendar-applications-protocol-back-btn' ).removeClass( 'fstu-hidden' );
			loadApplicationsProtocol();
		} );

		$( document ).on( 'click', '#fstu-calendar-applications-protocol-back-btn', function () {
			resetApplicationsProtocolView();
			initializeApplicationActionsAvailability();
		} );

		$( document ).on( 'input', '#fstu-calendar-applications-protocol-search', debounce( function () {
			state.applicationsProtocol.search = $( this ).val().trim();
			state.applicationsProtocol.page = 1;
			loadApplicationsProtocol();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-calendar-applications-protocol-per-page', function () {
			state.applicationsProtocol.perPage = parseInt( $( this ).val(), 10 ) || 10;
			state.applicationsProtocol.page = 1;
			loadApplicationsProtocol();
		} );

		$( document ).on( 'click', '#fstu-calendar-applications-protocol-prev-page', function () {
			if ( state.applicationsProtocol.page > 1 ) {
				state.applicationsProtocol.page--;
				loadApplicationsProtocol();
			}
		} );

		$( document ).on( 'click', '#fstu-calendar-applications-protocol-next-page', function () {
			if ( state.applicationsProtocol.page < state.applicationsProtocol.totalPages ) {
				state.applicationsProtocol.page++;
				loadApplicationsProtocol();
			}
		} );

		$( document ).on( 'click', '.fstu-calendar-applications-protocol-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			if ( page !== state.applicationsProtocol.page ) {
				state.applicationsProtocol.page = page;
				loadApplicationsProtocol();
			}
		} );

		$( document ).on( 'click', '#fstu-calendar-add-application-btn', function () {
			if ( state.applications.selectedEventId <= 0 ) {
				alert( 'Спершу оберіть захід у реєстрі.' );
				return;
			}
			openApplicationFormModal();
		} );

		$( document ).on( 'submit', '#fstu-calendar-application-form', function ( event ) {
			event.preventDefault();
			submitApplicationForm();
		} );

		$( document ).on( 'click', '.fstu-calendar-application-action-toggle', function ( event ) {
			event.preventDefault();
			event.stopPropagation();
			openApplicationsDropdown( $( this ) );
		} );

		$( document ).on( 'click', '#fstu-calendar-application-action-dropdown .fstu-calendar-application-action', function () {
			const action = $( this ).data( 'action' );
			const applicationId = parseInt( $( this ).data( 'application-id' ), 10 ) || 0;
			const targetStatusId = parseInt( $( this ).data( 'target-status-id' ), 10 ) || 0;
			closeDropdown();

			if ( action === 'view' ) {
				openApplicationViewModal( applicationId );
			} else if ( action === 'edit' ) {
				openApplicationFormModal( applicationId );
			} else if ( action === 'delete' ) {
				deleteApplication( applicationId );
			} else if ( action === 'change-status' ) {
				changeApplicationStatus( applicationId, targetStatusId );
			}
		} );

		$( document ).on( 'submit', '#fstu-calendar-application-add-participant-form', function ( event ) {
			event.preventDefault();
			addExistingParticipant();
		} );

		$( document ).on( 'submit', '#fstu-calendar-create-participant-form', function ( event ) {
			event.preventDefault();
			createParticipantUser();
		} );

		$( document ).on( 'click', '.fstu-calendar-remove-participant-btn', function () {
			const usercalendarId = parseInt( $( this ).data( 'usercalendar-id' ), 10 ) || 0;
			removeParticipant( usercalendarId );
		} );
	}

	function getApplicationStatusFlowMap() {
		const statuses = Array.isArray( datasets.app_statuses ) ? datasets.app_statuses : [];
		const map = {
			draft: 0,
			underReview: 0,
			approved: 0,
			needsFixes: 0
		};

		statuses.forEach( function ( status, index ) {
			const statusId = parseInt( status.StatusApp_ID, 10 ) || 0;
			const statusName = String( status.StatusApp_Name || '' ).toLowerCase();

			if ( ! map.draft && ( statusName.indexOf( 'чернет' ) !== -1 || statusName.indexOf( 'draft' ) !== -1 || index === 0 ) ) {
				map.draft = statusId;
				return;
			}

			if ( ! map.underReview && ( statusName.indexOf( 'розгляд' ) !== -1 || statusName.indexOf( 'review' ) !== -1 || index === 1 ) ) {
				map.underReview = statusId;
				return;
			}

			if ( ! map.approved && ( statusName.indexOf( 'підтвер' ) !== -1 || statusName.indexOf( 'approved' ) !== -1 || index === 2 ) ) {
				map.approved = statusId;
				return;
			}

			if ( ! map.needsFixes && ( statusName.indexOf( 'доопрац' ) !== -1 || statusName.indexOf( 'помил' ) !== -1 || statusName.indexOf( 'fix' ) !== -1 || index === 3 ) ) {
				map.needsFixes = statusId;
			}
		} );

		return map;
	}

	function getApplicationStatusId( application ) {
		return parseInt( application && application.StatusApp_ID ? application.StatusApp_ID : 0, 10 ) || 0;
	}

	function isApplicationOwner( application ) {
		const ownerId = parseInt( application && application.UserCreate ? application.UserCreate : 0, 10 ) || 0;
		const currentUserId = parseInt( fstuCalendarL10n.currentUserId, 10 ) || 0;
		return ownerId > 0 && ownerId === currentUserId;
	}

	function canUseApplicationOwnerFlow( application ) {
		return isApplicationOwner( application ) && ( applicationsPermissions.canSubmit || applicationsPermissions.canManage );
	}

	function canEditApplication( application ) {
		const statusId = getApplicationStatusId( application );
		if ( applicationStatusFlow.approved && statusId === applicationStatusFlow.approved ) {
			return false;
		}

		if ( applicationsPermissions.canManage ) {
			return true;
		}

		if ( ! canUseApplicationOwnerFlow( application ) ) {
			return false;
		}

		return ! applicationStatusFlow.underReview || statusId !== applicationStatusFlow.underReview;
	}

	function canDeleteApplication( application ) {
		return isApplicationOwner( application ) || !! applicationsPermissions.canDelete;
	}

	function canManageApplicationParticipants( application ) {
		const statusId = getApplicationStatusId( application );
		if ( applicationStatusFlow.approved && statusId === applicationStatusFlow.approved ) {
			return false;
		}

		if ( applicationsPermissions.canManage ) {
			return true;
		}

		if ( ! canUseApplicationOwnerFlow( application ) ) {
			return false;
		}

		return ! applicationStatusFlow.underReview || statusId !== applicationStatusFlow.underReview;
	}

	function canMoveApplicationToReview( application ) {
		const statusId = getApplicationStatusId( application );
		return canUseApplicationOwnerFlow( application ) && !! applicationStatusFlow.draft && !! applicationStatusFlow.underReview && statusId === applicationStatusFlow.draft;
	}

	function canMoveApplicationToDraft( application ) {
		const statusId = getApplicationStatusId( application );
		return canUseApplicationOwnerFlow( application ) && !! applicationStatusFlow.needsFixes && !! applicationStatusFlow.draft && statusId === applicationStatusFlow.needsFixes;
	}

	function canApproveApplication( application ) {
		const statusId = getApplicationStatusId( application );
		return !! applicationsPermissions.canManage && !! applicationStatusFlow.underReview && !! applicationStatusFlow.approved && statusId === applicationStatusFlow.underReview;
	}

	function canReturnApplicationForFixes( application ) {
		const statusId = getApplicationStatusId( application );
		return !! applicationsPermissions.canManage && !! applicationStatusFlow.underReview && !! applicationStatusFlow.needsFixes && statusId === applicationStatusFlow.underReview;
	}

	function bindResults() {
		$( document ).on( 'input', '#fstu-calendar-results-search', debounce( function () {
			state.results.search = $( this ).val().trim();
			state.results.page = 1;
			loadResults();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-calendar-results-per-page', function () {
			state.results.perPage = parseInt( $( this ).val(), 10 ) || 10;
			state.results.page = 1;
			loadResults();
		} );

		$( document ).on( 'click', '#fstu-calendar-results-prev-page', function () {
			if ( state.results.page > 1 ) {
				state.results.page--;
				loadResults();
			}
		} );

		$( document ).on( 'click', '#fstu-calendar-results-next-page', function () {
			if ( state.results.page < state.results.totalPages ) {
				state.results.page++;
				loadResults();
			}
		} );

		$( document ).on( 'click', '.fstu-calendar-results-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			if ( page !== state.results.page ) {
				state.results.page = page;
				loadResults();
			}
		} );

		$( document ).on( 'click', '#fstu-calendar-results-back-to-events', function () {
			activateShellTab( 'registry' );
		} );

		$( document ).on( 'click', '#fstu-calendar-add-race-btn', function () {
			if ( state.results.selectedEventId <= 0 ) {
				alert( 'Спершу оберіть захід у реєстрі.' );
				return;
			}
			openRaceFormModal();
		} );

		$( document ).on( 'submit', '#fstu-calendar-race-form', function ( event ) {
			event.preventDefault();
			submitRaceForm();
		} );

		$( document ).on( 'click', '.fstu-calendar-results-action-toggle', function ( event ) {
			event.preventDefault();
			event.stopPropagation();
			openResultsDropdown( $( this ) );
		} );

		$( document ).on( 'click', '#fstu-calendar-results-action-dropdown .fstu-calendar-results-action', function () {
			const action = $( this ).data( 'action' );
			const raceId = parseInt( $( this ).data( 'race-id' ), 10 ) || 0;
			closeDropdown();

			if ( action === 'view' ) {
				openRaceViewModal( raceId );
			} else if ( action === 'edit' ) {
				openRaceFormModal( raceId );
			} else if ( action === 'delete' ) {
				deleteRace( raceId );
			} else if ( action === 'recalculate' ) {
				recalculateRaceResults( raceId );
			}
		} );

		$( document ).on( 'click', '#fstu-calendar-results-protocol-btn', function () {
			$( '#fstu-calendar-results-main-content' ).addClass( 'fstu-hidden' );
			$( '#fstu-calendar-results-protocol' ).removeClass( 'fstu-hidden' );
			$( '#fstu-calendar-results-protocol-btn, #fstu-calendar-add-race-btn' ).addClass( 'fstu-hidden' );
			$( '#fstu-calendar-results-protocol-back-btn' ).removeClass( 'fstu-hidden' );
			loadResultsProtocol();
		} );

		$( document ).on( 'click', '#fstu-calendar-results-protocol-back-btn', function () {
			$( '#fstu-calendar-results-main-content' ).removeClass( 'fstu-hidden' );
			$( '#fstu-calendar-results-protocol' ).addClass( 'fstu-hidden' );
			$( '#fstu-calendar-results-protocol-btn' ).removeClass( 'fstu-hidden' );
			$( '#fstu-calendar-results-protocol-back-btn' ).addClass( 'fstu-hidden' );
			initializeResultsActionsAvailability();
		} );

		$( document ).on( 'input', '#fstu-calendar-results-protocol-search', debounce( function () {
			state.resultsProtocol.search = $( this ).val().trim();
			state.resultsProtocol.page = 1;
			loadResultsProtocol();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-calendar-results-protocol-per-page', function () {
			state.resultsProtocol.perPage = parseInt( $( this ).val(), 10 ) || 10;
			state.resultsProtocol.page = 1;
			loadResultsProtocol();
		} );

		$( document ).on( 'click', '#fstu-calendar-results-protocol-prev-page', function () {
			if ( state.resultsProtocol.page > 1 ) {
				state.resultsProtocol.page--;
				loadResultsProtocol();
			}
		} );

		$( document ).on( 'click', '#fstu-calendar-results-protocol-next-page', function () {
			if ( state.resultsProtocol.page < state.resultsProtocol.totalPages ) {
				state.resultsProtocol.page++;
				loadResultsProtocol();
			}
		} );

		$( document ).on( 'click', '.fstu-calendar-results-protocol-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			if ( page !== state.resultsProtocol.page ) {
				state.resultsProtocol.page = page;
				loadResultsProtocol();
			}
		} );

		$( document ).on( 'click', '#fstu-calendar-race-recalculate-btn', function () {
			const raceId = parseInt( $( this ).data( 'race-id' ), 10 ) || state.results.selectedRaceId || 0;
			if ( raceId > 0 ) {
				recalculateRaceResults( raceId, true );
			}
		} );

		$( document ).on( 'click', '#fstu-calendar-race-edit-protocol-btn', function () {
			state.results.protocolEditing = true;
			renderRaceProtocolTable( state.results.protocolItems || [] );
			toggleRaceProtocolButtons();
		} );

		$( document ).on( 'click', '#fstu-calendar-race-cancel-protocol-btn', function () {
			state.results.protocolEditing = false;
			renderRaceProtocolTable( state.results.protocolItems || [] );
			toggleRaceProtocolButtons();
		} );

		$( document ).on( 'click', '#fstu-calendar-race-save-protocol-btn', function () {
			submitRaceProtocol();
		} );
	}

	function bindProtocol() {
		$( document ).on( 'input', '#fstu-calendar-protocol-search', debounce( function () {
			state.protocol.search = $( this ).val().trim();
			state.protocol.page = 1;
			loadProtocol();
		}, 300 ) );

		$( document ).on( 'change', '#fstu-calendar-protocol-per-page', function () {
			state.protocol.perPage = parseInt( $( this ).val(), 10 ) || 10;
			state.protocol.page = 1;
			loadProtocol();
		} );

		$( document ).on( 'click', '#fstu-calendar-protocol-prev-page', function () {
			if ( state.protocol.page > 1 ) {
				state.protocol.page--;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '#fstu-calendar-protocol-next-page', function () {
			if ( state.protocol.page < state.protocol.totalPages ) {
				state.protocol.page++;
				loadProtocol();
			}
		} );

		$( document ).on( 'click', '.fstu-calendar-protocol-page-btn', function () {
			const page = parseInt( $( this ).data( 'page' ), 10 ) || 1;
			if ( page !== state.protocol.page ) {
				state.protocol.page = page;
				loadProtocol();
			}
		} );
	}

	function bindViewNavigation() {
		$( document ).on( 'click', '[data-calendar-view]', function () {
			state.calendar.mode = $( this ).data( 'calendar-view' );
			$( '[data-calendar-view]' ).removeClass( 'is-active' );
			$( this ).addClass( 'is-active' );
			loadCalendarView();
		} );

		$( document ).on( 'click', '#fstu-calendar-view-prev', function () {
			if ( state.calendar.mode === 'month' ) {
				state.calendar.month--;
				if ( state.calendar.month < 1 ) {
					state.calendar.month = 12;
					state.calendar.year--;
					$( '#fstu-calendar-year' ).val( state.calendar.year );
				}
			} else {
				state.calendar.weekStart = addDays( state.calendar.weekStart, -7 );
			}
			loadCalendarView();
		} );

		$( document ).on( 'click', '#fstu-calendar-view-next', function () {
			if ( state.calendar.mode === 'month' ) {
				state.calendar.month++;
				if ( state.calendar.month > 12 ) {
					state.calendar.month = 1;
					state.calendar.year++;
					$( '#fstu-calendar-year' ).val( state.calendar.year );
				}
			} else {
				state.calendar.weekStart = addDays( state.calendar.weekStart, 7 );
			}
			loadCalendarView();
		} );
	}

	function bindModals() {
		$( document ).on( 'click', '[data-close-modal]', function () {
			closeModal( $( this ).data( 'close-modal' ) );
		} );

		$( document ).on( 'click', '.fstu-modal-overlay', function ( event ) {
			if ( $( event.target ).is( '.fstu-modal-overlay' ) ) {
				closeModal( $( this ).attr( 'id' ) );
			}
		} );
	}

	function bindActions() {
		$( document ).on( 'click', '#fstu-calendar-add-event-btn', function () {
			openFormModal();
		} );

		$( document ).on( 'submit', '#fstu-calendar-form', function ( event ) {
			event.preventDefault();
			submitEventForm();
		} );

		$( document ).on( 'click', '.fstu-calendar-action-toggle', function ( event ) {
			event.preventDefault();
			event.stopPropagation();
			openDropdown( $( this ) );
		} );

		$( document ).on( 'click', function ( event ) {
			if ( dropdownState && ! $( event.target ).closest( '#fstu-calendar-action-dropdown, #fstu-calendar-application-action-dropdown, #fstu-calendar-results-action-dropdown, .fstu-calendar-action-toggle, .fstu-calendar-application-action-toggle, .fstu-calendar-results-action-toggle' ).length ) {
				closeDropdown();
			}
		} );

		$( document ).on( 'scroll', function () {
			closeDropdown();
		}, true );

		$( document ).on( 'click', '#fstu-calendar-action-dropdown .fstu-calendar-action', function () {
			const action = $( this ).data( 'action' );
			const eventId = parseInt( $( this ).data( 'event-id' ), 10 ) || 0;
			const eventName = $( this ).data( 'event-name' ) || '';
			closeDropdown();

			if ( action === 'view' ) {
				openViewModal( eventId );
			} else if ( action === 'edit' ) {
				openFormModal( eventId );
			} else if ( action === 'delete' ) {
				deleteEvent( eventId );
			} else if ( action === 'applications' ) {
				selectEventForApplications( eventId, eventName );
			} else if ( action === 'results' ) {
				selectEventForResults( eventId, eventName );
			}
		} );
	}

	function loadEvents() {
		const requestData = {
			action: 'fstu_calendar_get_events',
			nonce: fstuCalendarL10n.nonce,
			page: state.list.page,
			per_page: state.list.perPage,
			search: state.list.search,
			year: state.list.year,
			status_id: state.list.statusId,
			region_id: state.list.regionId,
			tourism_type_id: state.list.tourismTypeId,
			event_type_id: state.list.eventTypeId
		};

		$( '#fstu-calendar-tbody' ).html( '<tr><td colspan="9" class="fstu-no-results">' + escHtml( fstuCalendarL10n.messages.loading ) + '</td></tr>' );

		$.post( fstuCalendarL10n.ajaxUrl, requestData )
			.done( function ( response ) {
				if ( ! response || ! response.success ) {
					renderEmptyList( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
					return;
				}

				state.list.total = parseInt( response.data.total, 10 ) || 0;
				state.list.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				renderList( response.data.items || [] );
				renderPagination( '#fstu-calendar-pagination-pages', '#fstu-calendar-pagination-info', '.fstu-calendar-page-btn', state.list );
			} )
			.fail( function () {
				renderEmptyList( fstuCalendarL10n.messages.error );
			} );
	}

	function renderList( items ) {
		if ( ! items.length ) {
			renderEmptyList( fstuCalendarL10n.messages.empty );
			return;
		}

		let html = '';
		items.forEach( function ( item, index ) {
			const responsible = item.Responsible_FullName || '—';
				html += '<tr>' +
				'<td>' + escHtml( String( ( ( state.list.page - 1 ) * state.list.perPage ) + index + 1 ) ) + '</td>' +
				'<td>' + escHtml( item.Calendar_Name || '' ) + '</td>' +
				'<td>' + escHtml( formatDate( item.Calendar_DateBegin ) ) + '</td>' +
				'<td>' + escHtml( formatDate( item.Calendar_DateEnd ) ) + '</td>' +
				'<td>' + escHtml( item.CalendarStatus_Name || '—' ) + '</td>' +
				'<td>' + escHtml( item.City_Name || '—' ) + '</td>' +
				'<td>' + escHtml( item.EventType_Name || '—' ) + '</td>' +
				'<td>' + escHtml( responsible ) + '</td>' +
				'<td><button type="button" class="fstu-dropdown-toggle fstu-calendar-action-toggle" data-event-id="' + escAttr( item.Calendar_ID ) + '" data-event-name="' + escAttr( item.Calendar_Name || '' ) + '" aria-label="Дії">▼</button></td>' +
			'</tr>';
		} );

		$( '#fstu-calendar-tbody' ).html( html );
	}

	function renderEmptyList( message ) {
		$( '#fstu-calendar-tbody' ).html( '<tr><td colspan="9" class="fstu-no-results">' + escHtml( message ) + '</td></tr>' );
	}

	function loadApplications() {
		if ( state.applications.selectedEventId <= 0 ) {
			renderApplicationsEmpty( 'Оберіть захід у реєстрі, щоб переглянути заявки.' );
			return;
		}

		const requestData = {
			action: 'fstu_calendar_get_applications',
			nonce: fstuCalendarL10n.nonce,
			calendar_id: state.applications.selectedEventId,
			page: state.applications.page,
			per_page: state.applications.perPage,
			search: state.applications.search
		};

		$( '#fstu-calendar-applications-tbody' ).html( '<tr><td colspan="8" class="fstu-no-results">' + escHtml( fstuCalendarL10n.messages.loading ) + '</td></tr>' );

		$.post( fstuCalendarL10n.ajaxUrl, requestData )
			.done( function ( response ) {
				if ( ! response || ! response.success ) {
					renderApplicationsEmpty( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
					return;
				}

				state.applications.total = parseInt( response.data.total, 10 ) || 0;
				state.applications.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				renderApplicationsList( response.data.items || [] );
				renderPagination( '#fstu-calendar-applications-pagination-pages', '#fstu-calendar-applications-pagination-info', '.fstu-calendar-applications-page-btn', state.applications, false, 'fstu-calendar-applications-page-btn' );
			} )
			.fail( function () {
				renderApplicationsEmpty( fstuCalendarL10n.messages.error );
			} );
	}

	function renderApplicationsList( items ) {
		if ( ! items.length ) {
			renderApplicationsEmpty( fstuCalendarL10n.messages.empty );
			return;
		}

		let html = '';
		items.forEach( function ( item, index ) {
			const title = item.App_Name || item.Sailboat_Name || ( 'Заявка #' + item.application_id );
			const statusId = parseInt( item.StatusApp_ID, 10 ) || 0;
			const ownerId = parseInt( item.UserCreate, 10 ) || 0;
			html += '<tr>' +
				'<td>' + escHtml( String( ( ( state.applications.page - 1 ) * state.applications.perPage ) + index + 1 ) ) + '</td>' +
				'<td>' + escHtml( title ) + '</td>' +
				'<td>' + escHtml( item.Creator_FullName || '—' ) + '</td>' +
				'<td>' + escHtml( item.StatusApp_Name || '—' ) + '</td>' +
				'<td>' + escHtml( item.Region_Name || '—' ) + '</td>' +
				'<td>' + escHtml( item.App_Phone || '—' ) + '</td>' +
				'<td>' + escHtml( String( item.Participants_Count || 0 ) ) + '</td>' +
				'<td><button type="button" class="fstu-dropdown-toggle fstu-calendar-application-action-toggle" data-application-id="' + escAttr( item.application_id ) + '" data-status-id="' + escAttr( statusId ) + '" data-owner-id="' + escAttr( ownerId ) + '" aria-label="Дії">▼</button></td>' +
			'</tr>';
		} );

		$( '#fstu-calendar-applications-tbody' ).html( html );
	}

	function renderApplicationsEmpty( message ) {
		$( '#fstu-calendar-applications-tbody' ).html( '<tr><td colspan="8" class="fstu-no-results">' + escHtml( message ) + '</td></tr>' );
	}

	function loadApplicationsProtocol() {
		const requestData = {
			action: 'fstu_calendar_get_applications_protocol',
			nonce: fstuCalendarL10n.nonce,
			page: state.applicationsProtocol.page,
			per_page: state.applicationsProtocol.perPage,
			search: state.applicationsProtocol.search
		};

		$( '#fstu-calendar-applications-protocol-tbody' ).html( '<tr><td colspan="6" class="fstu-no-results">' + escHtml( fstuCalendarL10n.messages.loading ) + '</td></tr>' );

		$.post( fstuCalendarL10n.ajaxUrl, requestData )
			.done( function ( response ) {
				if ( ! response || ! response.success ) {
					$( '#fstu-calendar-applications-protocol-tbody' ).html( '<tr><td colspan="6" class="fstu-no-results">' + escHtml( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error ) + '</td></tr>' );
					return;
				}

				state.applicationsProtocol.total = parseInt( response.data.total, 10 ) || 0;
				state.applicationsProtocol.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				renderApplicationsProtocolRows( response.data.items || [] );
				renderPagination( '#fstu-calendar-applications-protocol-pagination-pages', '#fstu-calendar-applications-protocol-pagination-info', '.fstu-calendar-applications-protocol-page-btn', state.applicationsProtocol, true, 'fstu-calendar-applications-protocol-page-btn' );
			} )
			.fail( function () {
				$( '#fstu-calendar-applications-protocol-tbody' ).html( '<tr><td colspan="6" class="fstu-no-results">' + escHtml( fstuCalendarL10n.messages.error ) + '</td></tr>' );
			} );
	}

	function renderApplicationsProtocolRows( items ) {
		if ( ! items.length ) {
			$( '#fstu-calendar-applications-protocol-tbody' ).html( '<tr><td colspan="6" class="fstu-no-results">' + escHtml( fstuCalendarL10n.messages.protocolEmpty ) + '</td></tr>' );
			return;
		}

		let html = '';
		items.forEach( function ( item ) {
			html += '<tr>' +
				'<td>' + escHtml( item.Logs_DateCreate || '—' ) + '</td>' +
				'<td>' + buildTypeBadge( item.Logs_Type ) + '</td>' +
				'<td>' + escHtml( item.Logs_Name || '—' ) + '</td>' +
				'<td>' + escHtml( item.Logs_Text || '—' ) + '</td>' +
				'<td>' + escHtml( item.Logs_Error || '—' ) + '</td>' +
				'<td>' + escHtml( item.FIO || '—' ) + '</td>' +
			'</tr>';
		} );

		$( '#fstu-calendar-applications-protocol-tbody' ).html( html );
	}

	function loadResults() {
		if ( state.results.selectedEventId <= 0 ) {
			renderResultsEmpty( 'Оберіть захід у реєстрі, щоб переглянути перегони.' );
			return;
		}

		const requestData = {
			action: 'fstu_calendar_get_races',
			nonce: fstuCalendarL10n.nonce,
			calendar_id: state.results.selectedEventId,
			page: state.results.page,
			per_page: state.results.perPage,
			search: state.results.search
		};

		$( '#fstu-calendar-results-tbody' ).html( '<tr><td colspan="7" class="fstu-no-results">' + escHtml( fstuCalendarL10n.messages.loading ) + '</td></tr>' );

		$.post( fstuCalendarL10n.ajaxUrl, requestData )
			.done( function ( response ) {
				if ( ! response || ! response.success ) {
					renderResultsEmpty( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
					return;
				}

				state.results.total = parseInt( response.data.total, 10 ) || 0;
				state.results.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				renderResultsList( response.data.items || [] );
				renderPagination( '#fstu-calendar-results-pagination-pages', '#fstu-calendar-results-pagination-info', '.fstu-calendar-results-page-btn', state.results, false, 'fstu-calendar-results-page-btn' );
			} )
			.fail( function () {
				renderResultsEmpty( fstuCalendarL10n.messages.error );
			} );
	}

	function renderResultsList( items ) {
		if ( ! items.length ) {
			renderResultsEmpty( fstuCalendarL10n.messages.empty );
			return;
		}

		let html = '';
		items.forEach( function ( item, index ) {
			const protocolCount = parseInt( item.Protocol_Count, 10 ) || 0;
			const resultCount = parseInt( item.Result_Count, 10 ) || 0;
			html += '<tr>' +
				'<td>' + escHtml( String( ( ( state.results.page - 1 ) * state.results.perPage ) + index + 1 ) ) + '</td>' +
				'<td>' + escHtml( item.Race_Name || ( 'Перегін #' + ( item.race_number || item.race_id || '' ) ) ) + '</td>' +
				'<td>' + escHtml( formatDate( item.race_date ) ) + '</td>' +
				'<td>' + escHtml( String( item.race_number || '—' ) ) + '</td>' +
				'<td>' + escHtml( item.race_type_name || '—' ) + '</td>' +
				'<td>' + escHtml( String( protocolCount ) ) + ' / ' + escHtml( String( resultCount ) ) + '</td>' +
				'<td><button type="button" class="fstu-dropdown-toggle fstu-calendar-results-action-toggle" data-race-id="' + escAttr( item.race_id ) + '" aria-label="Дії">▼</button></td>' +
			'</tr>';
		} );

		$( '#fstu-calendar-results-tbody' ).html( html );
	}

	function renderResultsEmpty( message ) {
		$( '#fstu-calendar-results-tbody' ).html( '<tr><td colspan="7" class="fstu-no-results">' + escHtml( message ) + '</td></tr>' );
	}

	function loadResultsProtocol() {
		const requestData = {
			action: 'fstu_calendar_get_results_protocol',
			nonce: fstuCalendarL10n.nonce,
			page: state.resultsProtocol.page,
			per_page: state.resultsProtocol.perPage,
			search: state.resultsProtocol.search
		};

		$( '#fstu-calendar-results-protocol-tbody' ).html( '<tr><td colspan="6" class="fstu-no-results">' + escHtml( fstuCalendarL10n.messages.loading ) + '</td></tr>' );

		$.post( fstuCalendarL10n.ajaxUrl, requestData )
			.done( function ( response ) {
				if ( ! response || ! response.success ) {
					$( '#fstu-calendar-results-protocol-tbody' ).html( '<tr><td colspan="6" class="fstu-no-results">' + escHtml( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error ) + '</td></tr>' );
					return;
				}

				state.resultsProtocol.total = parseInt( response.data.total, 10 ) || 0;
				state.resultsProtocol.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				renderResultsProtocolRows( response.data.items || [] );
				renderPagination( '#fstu-calendar-results-protocol-pagination-pages', '#fstu-calendar-results-protocol-pagination-info', '.fstu-calendar-results-protocol-page-btn', state.resultsProtocol, true, 'fstu-calendar-results-protocol-page-btn' );
			} )
			.fail( function () {
				$( '#fstu-calendar-results-protocol-tbody' ).html( '<tr><td colspan="6" class="fstu-no-results">' + escHtml( fstuCalendarL10n.messages.error ) + '</td></tr>' );
			} );
	}

	function renderResultsProtocolRows( items ) {
		if ( ! items.length ) {
			$( '#fstu-calendar-results-protocol-tbody' ).html( '<tr><td colspan="6" class="fstu-no-results">' + escHtml( fstuCalendarL10n.messages.protocolEmpty ) + '</td></tr>' );
			return;
		}

		let html = '';
		items.forEach( function ( item ) {
			html += '<tr>' +
				'<td>' + escHtml( item.Logs_DateCreate || '—' ) + '</td>' +
				'<td>' + buildTypeBadge( item.Logs_Type ) + '</td>' +
				'<td>' + escHtml( item.Logs_Name || '—' ) + '</td>' +
				'<td>' + escHtml( item.Logs_Text || '—' ) + '</td>' +
				'<td>' + escHtml( item.Logs_Error || '—' ) + '</td>' +
				'<td>' + escHtml( item.FIO || '—' ) + '</td>' +
			'</tr>';
		} );

		$( '#fstu-calendar-results-protocol-tbody' ).html( html );
	}

	function openRaceViewModal( raceId ) {
		$.post( fstuCalendarL10n.ajaxUrl, {
			action: 'fstu_calendar_get_race',
			nonce: fstuCalendarL10n.nonce,
			race_id: raceId
		} ).done( function ( response ) {
			if ( ! response || ! response.success ) {
				alert( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
				return;
			}

			const item = response.data.item || {};
			state.results.selectedRaceId = raceId;
			state.results.protocolItems = cloneProtocolItems( item.protocol_items || [] );
			state.results.protocolEditing = false;
			let html = '';
			html += buildViewField( 'Найменування', item.Race_Name || ( 'Перегін #' + ( item.race_number || raceId ) ) );
			html += buildViewField( 'Дата', formatDate( item.race_date ) );
			html += buildViewField( 'Номер', item.race_number || '—' );
			html += buildViewField( 'Тип', item.race_type_name || '—' );
			html += buildViewField( 'Захід', state.results.selectedEventName || '—' );
			html += buildViewField( 'Опис', item.Race_Description || item.Race_Note || '—' );
			$( '#fstu-calendar-race-view-content' ).html( html );
			$( '#fstu-calendar-race-recalculate-btn' ).data( 'race-id', raceId );
			renderRaceProtocolTable( state.results.protocolItems || [] );
			renderRaceResultsTable( item.result_items || [] );
			toggleRaceProtocolButtons();
			openModal( 'fstu-calendar-race-view-modal' );
		} );
	}

	function renderRaceProtocolTable( items ) {
		if ( ! items.length ) {
			$( '#fstu-calendar-race-protocol-tbody' ).html( '<tr><td colspan="6" class="fstu-no-results">Записи відсутні.</td></tr>' );
			return;
		}

		let html = '';
		items.forEach( function ( item, index ) {
			if ( state.results.protocolEditing && resultsPermissions.canManage ) {
				html += '<tr data-protocol-index="' + index + '" data-protocol-id="' + escAttr( item.protocol_id || 0 ) + '">' +
					'<td><input type="number" class="fstu-input fstu-input--compact fstu-calendar-protocol-input" data-field="protocol_place" value="' + escAttr( item.protocol_place || '' ) + '" min="0"></td>' +
					'<td>' + escHtml( item.sailboat_name || item.sail_number || '—' ) + '</td>' +
					'<td><input type="text" class="fstu-input fstu-input--compact fstu-calendar-protocol-input" data-field="protocol_start" value="' + escAttr( item.protocol_start || '' ) + '"></td>' +
					'<td><input type="text" class="fstu-input fstu-input--compact fstu-calendar-protocol-input" data-field="protocol_finish" value="' + escAttr( item.protocol_finish || '' ) + '"></td>' +
					'<td><input type="text" class="fstu-input fstu-input--compact fstu-calendar-protocol-input" data-field="protocol_result" value="' + escAttr( item.protocol_result || '' ) + '"></td>' +
					'<td><input type="text" class="fstu-input fstu-input--compact fstu-calendar-protocol-input" data-field="protocol_note" value="' + escAttr( item.protocol_note || '' ) + '"></td>' +
				'</tr>';
				return;
			}

			html += '<tr>' +
				'<td>' + escHtml( item.protocol_place || '—' ) + '</td>' +
				'<td>' + escHtml( item.sailboat_name || item.sail_number || '—' ) + '</td>' +
				'<td>' + escHtml( item.protocol_start || '—' ) + '</td>' +
				'<td>' + escHtml( item.protocol_finish || '—' ) + '</td>' +
				'<td>' + escHtml( item.protocol_result || '—' ) + '</td>' +
				'<td>' + escHtml( item.protocol_note || '—' ) + '</td>' +
			'</tr>';
		} );

		$( '#fstu-calendar-race-protocol-tbody' ).html( html );
	}

	function toggleRaceProtocolButtons() {
		const canEditProtocol = resultsPermissions.canManage && Array.isArray( state.results.protocolItems ) && state.results.protocolItems.length > 0;
		$( '#fstu-calendar-race-edit-protocol-btn' ).toggleClass( 'fstu-hidden', ! canEditProtocol || state.results.protocolEditing );
		$( '#fstu-calendar-race-save-protocol-btn, #fstu-calendar-race-cancel-protocol-btn' ).toggleClass( 'fstu-hidden', ! canEditProtocol || ! state.results.protocolEditing );
		$( '#fstu-calendar-race-recalculate-btn' ).toggleClass( 'fstu-hidden', ! resultsPermissions.canManage || state.results.protocolEditing );
	}

	function submitRaceProtocol() {
		const raceId = state.results.selectedRaceId || 0;
		const items = collectRaceProtocolItemsFromForm();

		if ( raceId <= 0 || ! items.length ) {
			alert( 'Немає даних для збереження фінішного протоколу.' );
			return;
		}

		$.post( fstuCalendarL10n.ajaxUrl, {
			action: 'fstu_calendar_update_race_protocol',
			nonce: fstuCalendarL10n.nonce,
			race_id: raceId,
			fstu_website: $( '#fstu-calendar-race-protocol-honeypot' ).val() || '',
			protocol_items: JSON.stringify( items )
		} ).done( function ( response ) {
			if ( ! response || ! response.success ) {
				alert( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
				return;
			}

			state.results.protocolItems = cloneProtocolItems( items );
			state.results.protocolEditing = false;
			renderRaceProtocolTable( state.results.protocolItems );
			toggleRaceProtocolButtons();
			loadResults();
			alert( response.data && response.data.message ? response.data.message : 'Фінішний протокол успішно оновлено.' );
		} ).fail( function () {
			alert( fstuCalendarL10n.messages.error );
		} );
	}

	function collectRaceProtocolItemsFromForm() {
		const items = [];
		$( '#fstu-calendar-race-protocol-tbody tr[data-protocol-id]' ).each( function () {
			const $row = $( this );
			const item = {
				protocol_id: parseInt( $row.data( 'protocol-id' ), 10 ) || 0,
				protocol_place: 0,
				protocol_start: '',
				protocol_finish: '',
				protocol_result: '',
				protocol_note: ''
			};

			$row.find( '.fstu-calendar-protocol-input' ).each( function () {
				const field = $( this ).data( 'field' );
				if ( field === 'protocol_place' ) {
					item.protocol_place = parseInt( $( this ).val(), 10 ) || 0;
				} else if ( field ) {
					item[ field ] = String( $( this ).val() || '' ).trim();
				}
			} );

			if ( item.protocol_id > 0 ) {
				items.push( item );
			}
		} );

		return items;
	}

	function cloneProtocolItems( items ) {
		return JSON.parse( JSON.stringify( Array.isArray( items ) ? items : [] ) );
	}

	function renderRaceResultsTable( items ) {
		if ( ! items.length ) {
			$( '#fstu-calendar-race-results-tbody' ).html( '<tr><td colspan="4" class="fstu-no-results">Записи відсутні.</td></tr>' );
			return;
		}

		let html = '';
		items.forEach( function ( item ) {
			html += '<tr>' +
				'<td>' + escHtml( item.result_place || '—' ) + '</td>' +
				'<td>' + escHtml( item.sailboat_name || item.sail_number || '—' ) + '</td>' +
				'<td>' + escHtml( item.result_value || '—' ) + '</td>' +
				'<td>' + escHtml( item.result_points || '—' ) + '</td>' +
			'</tr>';
		} );

		$( '#fstu-calendar-race-results-tbody' ).html( html );
	}

	function openRaceFormModal( raceId ) {
		resetRaceForm();
		$( '#fstu-calendar-race-calendar-id' ).val( state.results.selectedEventId || 0 );
		if ( raceId ) {
			$( '#fstu-calendar-race-form-title' ).text( 'Редагування перегону' );
			$.post( fstuCalendarL10n.ajaxUrl, {
				action: 'fstu_calendar_get_race',
				nonce: fstuCalendarL10n.nonce,
				race_id: raceId
			} ).done( function ( response ) {
				if ( ! response || ! response.success ) {
					alert( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
					return;
				}
				fillRaceForm( response.data.item || {} );
				openModal( 'fstu-calendar-race-form-modal' );
			} );
		} else {
			$( '#fstu-calendar-race-form-title' ).text( 'Додавання перегону' );
			openModal( 'fstu-calendar-race-form-modal' );
		}
	}

	function fillRaceForm( item ) {
		$( '#fstu-calendar-race-id' ).val( item.race_id || 0 );
		$( '#fstu-calendar-race-calendar-id' ).val( item.Calendar_ID || state.results.selectedEventId || 0 );
		$( '#fstu-calendar-race-date' ).val( toDateInput( item.race_date ) );
		$( '#fstu-calendar-race-number' ).val( item.race_number || '' );
		$( '#fstu-calendar-race-name' ).val( item.Race_Name || '' );
		$( '#fstu-calendar-race-type-id' ).val( item.RaceType_ID || 0 );
		$( '#fstu-calendar-race-description' ).val( item.Race_Description || item.Race_Note || '' );
	}

	function resetRaceForm() {
		if ( $( '#fstu-calendar-race-form' ).length ) {
			$( '#fstu-calendar-race-form' )[0].reset();
		}
		$( '#fstu-calendar-race-id' ).val( 0 );
		$( '#fstu-calendar-race-calendar-id' ).val( state.results.selectedEventId || 0 );
	}

	function submitRaceForm() {
		const raceId = parseInt( $( '#fstu-calendar-race-id' ).val(), 10 ) || 0;
		const action = raceId > 0 ? 'fstu_calendar_update_race' : 'fstu_calendar_create_race';
		const formData = $( '#fstu-calendar-race-form' ).serializeArray();
		formData.push( { name: 'action', value: action } );

		$.post( fstuCalendarL10n.ajaxUrl, $.param( formData ) )
			.done( function ( response ) {
				if ( ! response || ! response.success ) {
					alert( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
					return;
				}
				closeModal( 'fstu-calendar-race-form-modal' );
				loadResults();
				alert( response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.saveSuccess );
			} )
			.fail( function () {
				alert( fstuCalendarL10n.messages.error );
			} );
	}

	function deleteRace( raceId ) {
		if ( ! window.confirm( 'Ви дійсно хочете видалити цей перегін?' ) ) {
			return;
		}

		$.post( fstuCalendarL10n.ajaxUrl, {
			action: 'fstu_calendar_delete_race',
			nonce: fstuCalendarL10n.nonce,
			race_id: raceId
		} ).done( function ( response ) {
			if ( ! response || ! response.success ) {
				alert( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
				return;
			}
			loadResults();
			alert( response.data && response.data.message ? response.data.message : 'Перегін успішно видалено.' );
		} );
	}

	function recalculateRaceResults( raceId, reloadModal ) {
		$.post( fstuCalendarL10n.ajaxUrl, {
			action: 'fstu_calendar_recalculate_race_results',
			nonce: fstuCalendarL10n.nonce,
			race_id: raceId
		} ).done( function ( response ) {
			if ( ! response || ! response.success ) {
				alert( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
				return;
			}
			loadResults();
			if ( reloadModal ) {
				openRaceViewModal( raceId );
			}
			var message = response.data && response.data.message ? response.data.message : 'Перерахунок результатів завершено.';
			if ( response.data && response.data.strategy ) {
				message += '\nСтратегія: ' + response.data.strategy;
			}
			if ( response.data && Array.isArray( response.data.warnings ) && response.data.warnings.length ) {
				message += '\n' + response.data.warnings.join( '\n' );
			}
			alert( message );
		} );
	}

	function loadProtocol() {
		const requestData = {
			action: 'fstu_calendar_get_events_protocol',
			nonce: fstuCalendarL10n.nonce,
			page: state.protocol.page,
			per_page: state.protocol.perPage,
			search: state.protocol.search
		};

		$( '#fstu-calendar-protocol-tbody' ).html( '<tr><td colspan="6" class="fstu-no-results">' + escHtml( fstuCalendarL10n.messages.loading ) + '</td></tr>' );

		$.post( fstuCalendarL10n.ajaxUrl, requestData )
			.done( function ( response ) {
				if ( ! response || ! response.success ) {
					$( '#fstu-calendar-protocol-tbody' ).html( '<tr><td colspan="6" class="fstu-no-results">' + escHtml( fstuCalendarL10n.messages.error ) + '</td></tr>' );
					return;
				}

				state.protocol.total = parseInt( response.data.total, 10 ) || 0;
				state.protocol.totalPages = parseInt( response.data.total_pages, 10 ) || 1;
				renderProtocolRows( response.data.items || [] );
				renderPagination( '#fstu-calendar-protocol-pagination-pages', '#fstu-calendar-protocol-pagination-info', '.fstu-calendar-protocol-page-btn', state.protocol, true );
			} )
			.fail( function () {
				$( '#fstu-calendar-protocol-tbody' ).html( '<tr><td colspan="6" class="fstu-no-results">' + escHtml( fstuCalendarL10n.messages.error ) + '</td></tr>' );
			} );
	}

	function renderProtocolRows( items ) {
		if ( ! items.length ) {
			$( '#fstu-calendar-protocol-tbody' ).html( '<tr><td colspan="6" class="fstu-no-results">' + escHtml( fstuCalendarL10n.messages.protocolEmpty ) + '</td></tr>' );
			return;
		}

		let html = '';
		items.forEach( function ( item ) {
			html += '<tr>' +
				'<td>' + escHtml( item.Logs_DateCreate || '—' ) + '</td>' +
				'<td>' + buildTypeBadge( item.Logs_Type ) + '</td>' +
				'<td>' + escHtml( item.Logs_Name || '—' ) + '</td>' +
				'<td>' + escHtml( item.Logs_Text || '—' ) + '</td>' +
				'<td>' + escHtml( item.Logs_Error || '—' ) + '</td>' +
				'<td>' + escHtml( item.FIO || '—' ) + '</td>' +
			'</tr>';
		} );
		$( '#fstu-calendar-protocol-tbody' ).html( html );
	}

	function loadCalendarView() {
		const action = state.calendar.mode === 'week' ? 'fstu_calendar_get_calendar_week' : 'fstu_calendar_get_calendar_month';
		const requestData = {
			action: action,
			nonce: fstuCalendarL10n.nonce,
			year: state.list.year,
			month: state.calendar.month,
			week_start: state.calendar.weekStart,
			status_id: state.list.statusId,
			region_id: state.list.regionId,
			tourism_type_id: state.list.tourismTypeId,
			event_type_id: state.list.eventTypeId
		};

		$( '#fstu-calendar-view-content' ).html( '<div class="fstu-calendar-placeholder">' + escHtml( fstuCalendarL10n.messages.loading ) + '</div>' );
		$.post( fstuCalendarL10n.ajaxUrl, requestData )
			.done( function ( response ) {
				if ( ! response || ! response.success ) {
					$( '#fstu-calendar-view-content' ).html( '<div class="fstu-calendar-placeholder">' + escHtml( fstuCalendarL10n.messages.error ) + '</div>' );
					return;
				}
				renderCalendarView( response.data.items || [], response.data.period_start, response.data.period_end );
			} )
			.fail( function () {
				$( '#fstu-calendar-view-content' ).html( '<div class="fstu-calendar-placeholder">' + escHtml( fstuCalendarL10n.messages.error ) + '</div>' );
			} );
	}

	function renderCalendarView( items, periodStart, periodEnd ) {
		const startDate = new Date( periodStart );
		const endDate = new Date( periodEnd );
		const days = [];
		const current = new Date( startDate );

		while ( current <= endDate ) {
			days.push( new Date( current ) );
			current.setDate( current.getDate() + 1 );
		}

		let html = '<div class="fstu-calendar-grid ' + ( state.calendar.mode === 'week' ? 'fstu-calendar-grid--week' : 'fstu-calendar-grid--month' ) + '">';
		days.forEach( function ( day ) {
			const key = toDateKey( day );
			const dayEvents = items.filter( function ( item ) {
				const begin = new Date( item.Calendar_DateBegin );
				const end = new Date( item.Calendar_DateEnd );
				const testDay = new Date( key + 'T12:00:00' );
				return begin <= testDay && end >= testDay;
			} );

			html += '<div class="fstu-calendar-day">';
			html += '<div class="fstu-calendar-day__header">' + escHtml( day.toLocaleDateString( 'uk-UA', { day: '2-digit', month: '2-digit', weekday: 'short' } ) ) + '</div>';
			html += '<div class="fstu-calendar-day__body">';
			if ( ! dayEvents.length ) {
				html += '<span class="fstu-calendar-day__empty">—</span>';
			} else {
				dayEvents.forEach( function ( item ) {
					html += '<button type="button" class="fstu-calendar-event-card fstu-calendar-open-view" data-event-id="' + escAttr( item.Calendar_ID ) + '">' +
						'<strong>' + escHtml( item.Calendar_Name || '' ) + '</strong>' +
						'<span>' + escHtml( item.City_Name || '—' ) + '</span>' +
					'</button>';
				} );
			}
			html += '</div></div>';
		} );
		html += '</div>';

		$( '#fstu-calendar-view-caption' ).text( formatCaption( startDate, endDate ) );
		$( '#fstu-calendar-view-content' ).html( html );
	}

	function openViewModal( eventId ) {
		$.post( fstuCalendarL10n.ajaxUrl, {
			action: 'fstu_calendar_get_event',
			nonce: fstuCalendarL10n.nonce,
			event_id: eventId
		} ).done( function ( response ) {
			if ( ! response || ! response.success ) {
				alert( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
				return;
			}

			const item = response.data.item || {};
			let html = '';
			html += buildViewField( 'Найменування', item.Calendar_Name );
			html += buildViewField( 'Початок', formatDate( item.Calendar_DateBegin ) );
			html += buildViewField( 'Завершення', formatDate( item.Calendar_DateEnd ) );
			html += buildViewField( 'Статус', item.CalendarStatus_Name );
			html += buildViewField( 'Місто', item.City_Name );
			html += buildViewField( 'Область', item.Region_Name );
			html += buildViewField( 'Вид туризму', item.TourismType_Name );
			html += buildViewField( 'Тип заходу', item.EventType_Name );
			html += buildViewField( 'Вид змагань', item.TypeEvent_Name );
			html += buildViewField( 'Вид походу', item.TourType_Name );
			html += buildViewField( 'Відповідальний', item.Responsible_FullName || item.User_ID );
			html += buildViewField( 'URL регламенту', item.Calendar_UrlReglament );
			html += buildViewField( 'URL протоколу', item.Calendar_UrlProt );
			html += buildViewField( 'URL карти', item.Calendar_UrlMap );
			html += buildViewField( 'URL звіту', item.Calendar_UrlReport );
			$( '#fstu-calendar-event-view-content' ).html( html );
			openModal( 'fstu-calendar-view-modal' );
		} );
	}

	function openApplicationViewModal( applicationId ) {
		$.post( fstuCalendarL10n.ajaxUrl, {
			action: 'fstu_calendar_get_application',
			nonce: fstuCalendarL10n.nonce,
			application_id: applicationId
		} ).done( function ( response ) {
			if ( ! response || ! response.success ) {
				alert( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
				return;
			}

			const item = response.data.item || {};
			state.applications.currentItem = item;
			$( '#fstu-calendar-participant-application-id' ).val( applicationId );
			$( '#fstu-calendar-create-participant-application-id' ).val( applicationId );
			if ( canManageApplicationParticipants( item ) ) {
				$( '.fstu-calendar-participants-manage' ).removeClass( 'fstu-hidden' );
			} else {
				$( '.fstu-calendar-participants-manage' ).addClass( 'fstu-hidden' );
			}
			resetParticipantForms();
			let html = '';
			html += buildViewField( 'Заявка', item.App_Name || item.Sailboat_Name || '—' );
			html += buildViewField( 'Код заявки', item.application_id || '—' );
			html += buildViewField( 'Статус', item.StatusApp_Name || '—' );
			html += buildViewField( 'Створив', item.Creator_FullName || '—' );
			html += buildViewField( 'Область', item.Region_Name || '—' );
			html += buildViewField( 'Телефон', item.App_Phone || '—' );
			html += buildViewField( 'Номер команди', item.App_Number || '—' );
			html += buildViewField( 'Судно', item.Sailboat_Name || '—' );
			$( '#fstu-calendar-application-view-content' ).html( html );
			loadApplicationParticipants( applicationId );
			openModal( 'fstu-calendar-application-view-modal' );
		} );
	}

	function resetParticipantForms() {
		$( '#fstu-calendar-existing-participant-user-id' ).val( 0 );
		$( '#fstu-calendar-existing-participant-type' ).val( 0 );
		$( '#fstu-calendar-create-participant-form' )[0].reset();
		$( '#fstu-calendar-participant-unit-id' ).val( 0 );
		$( '#fstu-calendar-new-participant-type' ).val( 0 );
	}

	function openFormModal( eventId ) {
		resetForm();
		if ( eventId ) {
			$( '#fstu-calendar-form-title' ).text( 'Редагування заходу' );
			$.post( fstuCalendarL10n.ajaxUrl, {
				action: 'fstu_calendar_get_event',
				nonce: fstuCalendarL10n.nonce,
				event_id: eventId
			} ).done( function ( response ) {
				if ( ! response || ! response.success ) {
					alert( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
					return;
				}
				fillForm( response.data.item || {} );
				openModal( 'fstu-calendar-form-modal' );
			} );
		} else {
			$( '#fstu-calendar-form-title' ).text( 'Додавання заходу' );
			openModal( 'fstu-calendar-form-modal' );
		}
	}

	function openApplicationFormModal( applicationId ) {
		resetApplicationForm();
		$( '#fstu-calendar-application-calendar-id' ).val( state.applications.selectedEventId || 0 );
		if ( applicationId ) {
			$( '#fstu-calendar-application-form-title' ).text( 'Редагування заявки' );
			$.post( fstuCalendarL10n.ajaxUrl, {
				action: 'fstu_calendar_get_application',
				nonce: fstuCalendarL10n.nonce,
				application_id: applicationId
			} ).done( function ( response ) {
				if ( ! response || ! response.success ) {
					alert( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
					return;
				}
				fillApplicationForm( response.data.item || {} );
				openModal( 'fstu-calendar-application-form-modal' );
			} );
		} else {
			$( '#fstu-calendar-application-form-title' ).text( 'Додавання заявки' );
			openModal( 'fstu-calendar-application-form-modal' );
		}
	}

	function fillForm( item ) {
		$( '#fstu-calendar-event-id' ).val( item.Calendar_ID || 0 );
		$( '#fstu-calendar-name' ).val( item.Calendar_Name || '' );
		$( '#fstu-calendar-date-begin' ).val( toDateInput( item.Calendar_DateBegin ) );
		$( '#fstu-calendar-date-end' ).val( toDateInput( item.Calendar_DateEnd ) );
		$( '#fstu-calendar-status-id' ).val( item.CalendarStatus_ID || 0 );
		$( '#fstu-calendar-city-id' ).val( item.City_ID || 0 );
		$( '#fstu-calendar-tourism-type-id' ).val( item.TourismType_ID || 0 );
		$( '#fstu-calendar-event-type-id' ).val( item.EventType_ID || 0 );
		$( '#fstu-calendar-type-event-id' ).val( item.TypeEvent_ID || 0 );
		$( '#fstu-calendar-tour-type-id' ).val( item.TourType_ID || 0 );
		$( '#fstu-calendar-responsible-user-id' ).val( item.User_ID || fstuCalendarL10n.currentUserId || 0 );
		$( '#fstu-calendar-url-reglament' ).val( item.Calendar_UrlReglament || '' );
		$( '#fstu-calendar-url-protocol' ).val( item.Calendar_UrlProt || '' );
		$( '#fstu-calendar-url-map' ).val( item.Calendar_UrlMap || '' );
		$( '#fstu-calendar-url-report' ).val( item.Calendar_UrlReport || '' );
	}

	function resetForm() {
		$( '#fstu-calendar-form' )[0].reset();
		$( '#fstu-calendar-event-id' ).val( 0 );
		$( '#fstu-calendar-responsible-user-id' ).val( fstuCalendarL10n.currentUserId || 0 );
	}

	function fillApplicationForm( item ) {
		$( '#fstu-calendar-application-id' ).val( item.application_id || 0 );
		$( '#fstu-calendar-application-calendar-id' ).val( item.Calendar_ID || state.applications.selectedEventId || 0 );
		$( '#fstu-calendar-application-name' ).val( item.App_Name || '' );
		$( '#fstu-calendar-application-number' ).val( item.App_Number || '' );
		$( '#fstu-calendar-application-phone' ).val( item.App_Phone || '' );
		$( '#fstu-calendar-application-region' ).val( item.Region_ID || 0 );
		$( '#fstu-calendar-application-sailboat-id' ).val( item.Sailboat_ID || 0 );
		$( '#fstu-calendar-application-mr-id' ).val( item.MR_ID || 0 );
		$( '#fstu-calendar-application-sail-group-id' ).val( item.SailGroup_ID || 0 );
	}

	function resetApplicationForm() {
		if ( $( '#fstu-calendar-application-form' ).length ) {
			$( '#fstu-calendar-application-form' )[0].reset();
		}
		$( '#fstu-calendar-application-id' ).val( 0 );
		$( '#fstu-calendar-application-calendar-id' ).val( state.applications.selectedEventId || 0 );
	}

	function submitEventForm() {
		const eventId = parseInt( $( '#fstu-calendar-event-id' ).val(), 10 ) || 0;
		const action = eventId > 0 ? 'fstu_calendar_update_event' : 'fstu_calendar_create_event';
		const formData = $( '#fstu-calendar-form' ).serializeArray();
		formData.push( { name: 'action', value: action } );

		$.post( fstuCalendarL10n.ajaxUrl, $.param( formData ) )
			.done( function ( response ) {
				if ( ! response || ! response.success ) {
					alert( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
					return;
				}
				closeModal( 'fstu-calendar-form-modal' );
				loadEvents();
				loadCalendarView();
				alert( response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.saveSuccess );
			} )
			.fail( function () {
				alert( fstuCalendarL10n.messages.error );
			} );
	}

	function submitApplicationForm() {
		const applicationId = parseInt( $( '#fstu-calendar-application-id' ).val(), 10 ) || 0;
		const action = applicationId > 0 ? 'fstu_calendar_update_application' : 'fstu_calendar_create_application';
		const formData = $( '#fstu-calendar-application-form' ).serializeArray();
		formData.push( { name: 'action', value: action } );

		$.post( fstuCalendarL10n.ajaxUrl, $.param( formData ) )
			.done( function ( response ) {
				if ( ! response || ! response.success ) {
					alert( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
					return;
				}
				closeModal( 'fstu-calendar-application-form-modal' );
				loadApplications();
				alert( response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.saveSuccess );
			} )
			.fail( function () {
				alert( fstuCalendarL10n.messages.error );
			} );
	}

	function deleteEvent( eventId ) {
		if ( ! window.confirm( fstuCalendarL10n.messages.confirmDelete ) ) {
			return;
		}

		$.post( fstuCalendarL10n.ajaxUrl, {
			action: 'fstu_calendar_delete_event',
			nonce: fstuCalendarL10n.nonce,
			event_id: eventId
		} ).done( function ( response ) {
			if ( ! response || ! response.success ) {
				alert( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
				return;
			}
			loadEvents();
			loadCalendarView();
			alert( response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.deleteSuccess );
		} );
	}

	function deleteApplication( applicationId ) {
		if ( ! window.confirm( 'Ви дійсно хочете видалити цю заявку?' ) ) {
			return;
		}

		$.post( fstuCalendarL10n.ajaxUrl, {
			action: 'fstu_calendar_delete_application',
			nonce: fstuCalendarL10n.nonce,
			application_id: applicationId
		} ).done( function ( response ) {
			if ( ! response || ! response.success ) {
				alert( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
				return;
			}
			loadApplications();
			alert( response.data && response.data.message ? response.data.message : 'Заявку успішно видалено.' );
		} );
	}

	function changeApplicationStatus( applicationId, targetStatusId ) {
		if ( ! applicationId || ! targetStatusId ) {
			return;
		}

		$.post( fstuCalendarL10n.ajaxUrl, {
			action: 'fstu_calendar_change_application_status',
			nonce: fstuCalendarL10n.nonce,
			application_id: applicationId,
			target_status_id: targetStatusId
		} ).done( function ( response ) {
			if ( ! response || ! response.success ) {
				alert( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
				return;
			}

			loadApplications();
			alert( response.data && response.data.message ? response.data.message : 'Статус заявки успішно змінено.' );
		} );
	}

	function addExistingParticipant() {
		const applicationId = parseInt( $( '#fstu-calendar-participant-application-id' ).val(), 10 ) || 0;
		const userId = parseInt( $( '#fstu-calendar-existing-participant-user-id' ).val(), 10 ) || 0;
		const participationTypeId = parseInt( $( '#fstu-calendar-existing-participant-type' ).val(), 10 ) || 0;

		$.post( fstuCalendarL10n.ajaxUrl, {
			action: 'fstu_calendar_add_application_participant',
			nonce: fstuCalendarL10n.nonce,
			application_id: applicationId,
			user_id: userId,
			participation_type_id: participationTypeId,
			fstu_website: $( '#fstu-calendar-participant-add-honeypot' ).val() || ''
		} ).done( function ( response ) {
			if ( ! response || ! response.success ) {
				alert( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
				return;
			}

			loadApplicationParticipants( applicationId );
			loadApplications();
			$( '#fstu-calendar-existing-participant-user-id' ).val( 0 );
			$( '#fstu-calendar-existing-participant-type' ).val( 0 );
			alert( response.data && response.data.message ? response.data.message : 'Учасника успішно додано.' );
		} );
	}

	function createParticipantUser() {
		const applicationId = parseInt( $( '#fstu-calendar-create-participant-application-id' ).val(), 10 ) || 0;
		const payload = {
			action: 'fstu_calendar_create_participant_user',
			nonce: fstuCalendarL10n.nonce,
			application_id: applicationId,
			last_name: $( '#fstu-calendar-participant-last-name' ).val() || '',
			first_name: $( '#fstu-calendar-participant-first-name' ).val() || '',
			patronymic: $( '#fstu-calendar-participant-patronymic' ).val() || '',
			email: $( '#fstu-calendar-participant-email' ).val() || '',
			phone: $( '#fstu-calendar-participant-phone' ).val() || '',
			birth_date: $( '#fstu-calendar-participant-birth-date' ).val() || '',
			sex: $( '#fstu-calendar-participant-sex' ).val() || '',
			city_id: $( '#fstu-calendar-participant-city-id' ).val() || 0,
			unit_id: $( '#fstu-calendar-participant-unit-id' ).val() || 0,
			participation_type_id: $( '#fstu-calendar-new-participant-type' ).val() || 0,
			fstu_website: $( '#fstu-calendar-participant-create-honeypot' ).val() || ''
		};

		$.post( fstuCalendarL10n.ajaxUrl, payload ).done( function ( response ) {
			if ( ! response || ! response.success ) {
				alert( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
				return;
			}

			loadApplicationParticipants( applicationId );
			loadApplications();
			$( '#fstu-calendar-create-participant-form' )[0].reset();
			$( '#fstu-calendar-participant-unit-id' ).val( 0 );
			$( '#fstu-calendar-new-participant-type' ).val( 0 );
			alert( response.data && response.data.message ? response.data.message : 'Нового учасника створено.' );
		} );
	}

	function removeParticipant( usercalendarId ) {
		const applicationId = parseInt( $( '#fstu-calendar-participant-application-id' ).val(), 10 ) || 0;
		if ( ! usercalendarId ) {
			return;
		}

		if ( ! window.confirm( 'Ви дійсно хочете видалити учасника із заявки?' ) ) {
			return;
		}

		$.post( fstuCalendarL10n.ajaxUrl, {
			action: 'fstu_calendar_remove_application_participant',
			nonce: fstuCalendarL10n.nonce,
			usercalendar_id: usercalendarId
		} ).done( function ( response ) {
			if ( ! response || ! response.success ) {
				alert( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error );
				return;
			}

			loadApplicationParticipants( applicationId );
			loadApplications();
			alert( response.data && response.data.message ? response.data.message : 'Учасника видалено.' );
		} );
	}

	function openDropdown( $trigger ) {
		const eventId = parseInt( $trigger.data( 'event-id' ), 10 ) || 0;
		const eventName = $trigger.data( 'event-name' ) || '';
		if ( ! eventId ) {
			return;
		}

		let $dropdown = $( '#fstu-calendar-action-dropdown' );
		if ( ! $dropdown.length ) {
			$dropdown = $( '<div id="fstu-calendar-action-dropdown" class="fstu-dropdown-menu fstu-hidden"></div>' );
			$( 'body' ).append( $dropdown );
		}

		let html = '<button type="button" class="fstu-calendar-action" data-action="view" data-event-id="' + escAttr( eventId ) + '">Перегляд</button>';
		if ( applicationsPermissions.canSubmit || applicationsPermissions.canManage ) {
			html += '<button type="button" class="fstu-calendar-action" data-action="applications" data-event-id="' + escAttr( eventId ) + '" data-event-name="' + escAttr( eventName ) + '">Заявки</button>';
		}
		html += '<button type="button" class="fstu-calendar-action" data-action="results" data-event-id="' + escAttr( eventId ) + '" data-event-name="' + escAttr( eventName ) + '">Результати</button>';
		if ( eventPermissions.canManage ) {
			html += '<button type="button" class="fstu-calendar-action" data-action="edit" data-event-id="' + escAttr( eventId ) + '">Редагування</button>';
		}
		if ( eventPermissions.canDelete || eventPermissions.canManage ) {
			html += '<button type="button" class="fstu-calendar-action" data-action="delete" data-event-id="' + escAttr( eventId ) + '">Видалення</button>';
		}

		$dropdown.html( html ).removeClass( 'fstu-hidden' );

		const rect = $trigger[0].getBoundingClientRect();
		$dropdown.css( {
			position: 'fixed',
			top: rect.bottom + 4,
			left: Math.max( 8, rect.left - 120 + rect.width ),
			zIndex: 100001
		} );

		dropdownState = true;
	}

	function openApplicationsDropdown( $trigger ) {
		const applicationId = parseInt( $trigger.data( 'application-id' ), 10 ) || 0;
		const statusId = parseInt( $trigger.data( 'status-id' ), 10 ) || 0;
		const ownerId = parseInt( $trigger.data( 'owner-id' ), 10 ) || 0;
		if ( ! applicationId ) {
			return;
		}

		const application = {
			application_id: applicationId,
			StatusApp_ID: statusId,
			UserCreate: ownerId
		};

		let $dropdown = $( '#fstu-calendar-application-action-dropdown' );
		if ( ! $dropdown.length ) {
			$dropdown = $( '<div id="fstu-calendar-application-action-dropdown" class="fstu-dropdown-menu fstu-hidden"></div>' );
			$( 'body' ).append( $dropdown );
		}

		let html = '<button type="button" class="fstu-calendar-application-action" data-action="view" data-application-id="' + escAttr( applicationId ) + '">Перегляд</button>';
		if ( canEditApplication( application ) ) {
			html += '<button type="button" class="fstu-calendar-application-action" data-action="edit" data-application-id="' + escAttr( applicationId ) + '">Редагування</button>';
		}
		if ( canMoveApplicationToReview( application ) ) {
			html += '<button type="button" class="fstu-calendar-application-action" data-action="change-status" data-application-id="' + escAttr( applicationId ) + '" data-target-status-id="' + escAttr( applicationStatusFlow.underReview ) + '">На розгляд</button>';
		}
		if ( canMoveApplicationToDraft( application ) ) {
			html += '<button type="button" class="fstu-calendar-application-action" data-action="change-status" data-application-id="' + escAttr( applicationId ) + '" data-target-status-id="' + escAttr( applicationStatusFlow.draft ) + '">У чернетку</button>';
		}
		if ( canApproveApplication( application ) ) {
			html += '<button type="button" class="fstu-calendar-application-action" data-action="change-status" data-application-id="' + escAttr( applicationId ) + '" data-target-status-id="' + escAttr( applicationStatusFlow.approved ) + '">Підтвердити</button>';
		}
		if ( canReturnApplicationForFixes( application ) ) {
			html += '<button type="button" class="fstu-calendar-application-action" data-action="change-status" data-application-id="' + escAttr( applicationId ) + '" data-target-status-id="' + escAttr( applicationStatusFlow.needsFixes ) + '">На доопрацювання</button>';
		}
		if ( canDeleteApplication( application ) ) {
			html += '<button type="button" class="fstu-calendar-application-action" data-action="delete" data-application-id="' + escAttr( applicationId ) + '">Видалення</button>';
		}

		$dropdown.html( html ).removeClass( 'fstu-hidden' );
		const rect = $trigger[0].getBoundingClientRect();
		$dropdown.css( {
			position: 'fixed',
			top: rect.bottom + 4,
			left: Math.max( 8, rect.left - 120 + rect.width ),
			zIndex: 100001
		} );

		dropdownState = true;
	}

	function openResultsDropdown( $trigger ) {
		const raceId = parseInt( $trigger.data( 'race-id' ), 10 ) || 0;
		if ( ! raceId ) {
			return;
		}

		let $dropdown = $( '#fstu-calendar-results-action-dropdown' );
		if ( ! $dropdown.length ) {
			$dropdown = $( '<div id="fstu-calendar-results-action-dropdown" class="fstu-dropdown-menu fstu-hidden"></div>' );
			$( 'body' ).append( $dropdown );
		}

		let html = '<button type="button" class="fstu-calendar-results-action" data-action="view" data-race-id="' + escAttr( raceId ) + '">Перегляд</button>';
		if ( resultsPermissions.canManage ) {
			html += '<button type="button" class="fstu-calendar-results-action" data-action="edit" data-race-id="' + escAttr( raceId ) + '">Редагування</button>';
			html += '<button type="button" class="fstu-calendar-results-action" data-action="recalculate" data-race-id="' + escAttr( raceId ) + '">Перерахувати</button>';
		}
		if ( resultsPermissions.canDelete || resultsPermissions.canManage ) {
			html += '<button type="button" class="fstu-calendar-results-action" data-action="delete" data-race-id="' + escAttr( raceId ) + '">Видалення</button>';
		}

		$dropdown.html( html ).removeClass( 'fstu-hidden' );
		const rect = $trigger[0].getBoundingClientRect();
		$dropdown.css( {
			position: 'fixed',
			top: rect.bottom + 4,
			left: Math.max( 8, rect.left - 120 + rect.width ),
			zIndex: 100001
		} );

		dropdownState = true;
	}

	function closeDropdown() {
		$( '#fstu-calendar-action-dropdown' ).addClass( 'fstu-hidden' );
		$( '#fstu-calendar-application-action-dropdown' ).addClass( 'fstu-hidden' );
		$( '#fstu-calendar-results-action-dropdown' ).addClass( 'fstu-hidden' );
		dropdownState = null;
	}

	function renderPagination( pagesSelector, infoSelector, buttonClass, paginationState, isProtocol, customPrefix ) {
		const prefix = customPrefix || ( isProtocol ? 'fstu-calendar-protocol-page-btn' : 'fstu-calendar-page-btn' );
		let html = '';
		for ( let page = 1; page <= paginationState.totalPages; page++ ) {
			html += '<button type="button" class="fstu-btn--page ' + prefix + ( page === paginationState.page ? ' is-active' : '' ) + '" data-page="' + page + '">' + page + '</button>';
		}
		$( pagesSelector ).html( html );
		$( infoSelector ).text( 'Записів: ' + paginationState.total + ' | Сторінка ' + paginationState.page + ' з ' + paginationState.totalPages );
	}

	function selectEventForApplications( eventId, eventName ) {
		state.applications.selectedEventId = eventId;
		state.applications.selectedEventName = eventName || '';
		state.applications.currentItem = null;
		state.applications.page = 1;
		state.applications.search = '';
		resetApplicationsProtocolView();
		$( '#fstu-calendar-applications-search' ).val( '' );
		$( '#fstu-calendar-applications-context' ).text( 'Обраний захід: ' + ( eventName || ( 'ID ' + eventId ) ) );
		activateShellTab( 'applications' );
		loadApplications();
	}

	function selectEventForResults( eventId, eventName ) {
		state.results.selectedEventId = eventId;
		state.results.selectedEventName = eventName || '';
		state.results.page = 1;
		state.results.search = '';
		state.results.selectedRaceId = 0;
		$( '#fstu-calendar-results-search' ).val( '' );
		$( '#fstu-calendar-results-context' ).text( 'Обраний захід: ' + ( eventName || ( 'ID ' + eventId ) ) );
		activateShellTab( 'results' );
		loadResults();
	}

	function activateShellTab( target ) {
		$( '.fstu-shell-tab[data-target="' + target + '"]' ).trigger( 'click' );
	}

	function initializeApplicationActionsAvailability() {
		if ( ! applicationsPermissions.canSubmit && ! applicationsPermissions.canManage ) {
			$( '#fstu-calendar-add-application-btn' ).addClass( 'fstu-hidden' );
		} else if ( ! $( '#fstu-calendar-applications-protocol' ).is( ':visible' ) ) {
			$( '#fstu-calendar-add-application-btn' ).removeClass( 'fstu-hidden' );
		}

		if ( ! applicationsPermissions.canProtocol ) {
			$( '#fstu-calendar-applications-protocol-btn' ).addClass( 'fstu-hidden' );
		} else if ( ! $( '#fstu-calendar-applications-protocol' ).is( ':visible' ) ) {
			$( '#fstu-calendar-applications-protocol-btn' ).removeClass( 'fstu-hidden' );
		}
	}

	function resetApplicationsProtocolView() {
		$( '#fstu-calendar-applications-main-content' ).removeClass( 'fstu-hidden' );
		$( '#fstu-calendar-applications-protocol' ).addClass( 'fstu-hidden' );
		$( '#fstu-calendar-applications-protocol-back-btn' ).addClass( 'fstu-hidden' );
		if ( applicationsPermissions.canProtocol ) {
			$( '#fstu-calendar-applications-protocol-btn' ).removeClass( 'fstu-hidden' );
		} else {
			$( '#fstu-calendar-applications-protocol-btn' ).addClass( 'fstu-hidden' );
		}
		if ( applicationsPermissions.canSubmit || applicationsPermissions.canManage ) {
			$( '#fstu-calendar-add-application-btn' ).removeClass( 'fstu-hidden' );
		} else {
			$( '#fstu-calendar-add-application-btn' ).addClass( 'fstu-hidden' );
		}
	}

	function initializeResultsActionsAvailability() {
		if ( ! resultsPermissions.canManage ) {
			$( '#fstu-calendar-add-race-btn' ).addClass( 'fstu-hidden' );
		} else {
			$( '#fstu-calendar-add-race-btn' ).removeClass( 'fstu-hidden' );
		}

		if ( ! resultsPermissions.canProtocol ) {
			$( '#fstu-calendar-results-protocol-btn' ).addClass( 'fstu-hidden' );
		} else {
			$( '#fstu-calendar-results-protocol-btn' ).removeClass( 'fstu-hidden' );
		}
	}

	function loadApplicationParticipants( applicationId ) {
		$( '#fstu-calendar-application-participants-tbody' ).html( '<tr><td colspan="6" class="fstu-no-results">' + escHtml( fstuCalendarL10n.messages.loading ) + '</td></tr>' );
		$.post( fstuCalendarL10n.ajaxUrl, {
			action: 'fstu_calendar_get_application_participants',
			nonce: fstuCalendarL10n.nonce,
			application_id: applicationId
		} ).done( function ( response ) {
			if ( ! response || ! response.success ) {
				$( '#fstu-calendar-application-participants-tbody' ).html( '<tr><td colspan="6" class="fstu-no-results">' + escHtml( response && response.data && response.data.message ? response.data.message : fstuCalendarL10n.messages.error ) + '</td></tr>' );
				return;
			}

			const items = response.data.items || [];
			if ( ! items.length ) {
				$( '#fstu-calendar-application-participants-tbody' ).html( '<tr><td colspan="6" class="fstu-no-results">Учасники відсутні.</td></tr>' );
				return;
			}

			let html = '';
			items.forEach( function ( item ) {
				const actionHtml = canManageApplicationParticipants( state.applications.currentItem || {} )
					? '<button type="button" class="fstu-btn--page fstu-calendar-remove-participant-btn" data-usercalendar-id="' + escAttr( item.UserCalendar_ID || 0 ) + '">×</button>'
					: '—';
				html += '<tr>' +
					'<td>' + escHtml( item.FIO || item.FIOshort || '—' ) + '</td>' +
					'<td>' + escHtml( item.ParticipationType_Name || '—' ) + '</td>' +
					'<td>' + escHtml( item.City_Name || '—' ) + '</td>' +
					'<td>' + escHtml( item.Sex || '—' ) + '</td>' +
					'<td>' + escHtml( formatDate( item.BirthDate ) ) + '</td>' +
					'<td>' + actionHtml + '</td>' +
				'</tr>';
			} );
			$( '#fstu-calendar-application-participants-tbody' ).html( html );
		} );
	}

	function buildTypeBadge( type ) {
		let cls = 'fstu-badge--default';
		let label = type || '—';
		if ( type === 'I' ) { cls = 'fstu-badge--insert'; label = 'INSERT'; }
		if ( type === 'U' ) { cls = 'fstu-badge--update'; label = 'UPDATE'; }
		if ( type === 'D' ) { cls = 'fstu-badge--delete'; label = 'DELETE'; }
		return '<span class="fstu-badge ' + cls + '">' + escHtml( label ) + '</span>';
	}

	function buildViewField( label, value ) {
		return '<div class="fstu-calendar-view-field"><span class="fstu-calendar-view-field__label">' + escHtml( label ) + '</span><span class="fstu-calendar-view-field__value">' + escHtml( value || '—' ) + '</span></div>';
	}

	function openModal( modalId ) {
		$( '#' + modalId ).removeClass( 'fstu-hidden' );
	}

	function closeModal( modalId ) {
		$( '#' + modalId ).addClass( 'fstu-hidden' );
	}

	function escHtml( value ) {
		return String( value || '' )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}

	function escAttr( value ) {
		return escHtml( value );
	}

	function formatDate( value ) {
		if ( ! value ) {
			return '—';
		}
		const date = new Date( value );
		if ( Number.isNaN( date.getTime() ) ) {
			return value;
		}
		return date.toLocaleDateString( 'uk-UA' );
	}

	function toDateInput( value ) {
		if ( ! value ) {
			return '';
		}
		return String( value ).substring( 0, 10 );
	}

	function toDateKey( date ) {
		const year = date.getFullYear();
		const month = String( date.getMonth() + 1 ).padStart( 2, '0' );
		const day = String( date.getDate() ).padStart( 2, '0' );
		return year + '-' + month + '-' + day;
	}

	function formatCaption( startDate, endDate ) {
		return startDate.toLocaleDateString( 'uk-UA' ) + ' — ' + endDate.toLocaleDateString( 'uk-UA' );
	}

	function addDays( dateString, days ) {
		const date = new Date( dateString );
		date.setDate( date.getDate() + days );
		return date.toISOString().substring( 0, 10 );
	}

	function getMonday( date ) {
		const copy = new Date( date );
		const day = copy.getDay();
		const diff = copy.getDate() - day + ( day === 0 ? -6 : 1 );
		copy.setDate( diff );
		return copy.toISOString().substring( 0, 10 );
	}

	function debounce( callback, delay ) {
		let timer = null;
		return function () {
			const args = arguments;
			const context = this;
			clearTimeout( timer );
			timer = setTimeout( function () {
				callback.apply( context, args );
			}, delay );
		};
	}

	$( document ).on( 'click', '.fstu-calendar-open-view', function () {
		const eventId = parseInt( $( this ).data( 'event-id' ), 10 ) || 0;
		if ( eventId > 0 ) {
			openViewModal( eventId );
		}
	} );
} );

