<?php
/**
 * "Job" page.
 *
 * @author John Resig, 2008-2011
 * @author Jörn Zaefferer, 2012
 * @since 0.1.0
 * @package TestSwarm
 */

class JobPage extends Page {

	public function execute() {
		$action = JobAction::newFromContext( $this->getContext() );
		$action->doAction();

		$this->setAction( $action );
		$this->content = $this->initContent();
	}

	protected function initContent() {
		$request = $this->getContext()->getRequest();

		$this->setTitle( "Job status" );
		$this->bodyScripts[] = swarmpath( "js/jquery.js" );
		$this->bodyScripts[] = swarmpath( "js/job.js" );

		$error = $this->getAction()->getError();
		$data = $this->getAction()->getData();
		$html = '';

		if ( $error ) {
			$html .= html_tag( 'div', array( 'class' => 'alert alert-error' ), $error['info'] );
		}

		if ( !isset( $data["jobInfo"] ) ) {
			return $html;
		}

		$this->setSubTitle( '#' . $data["jobInfo"]["id"] );

		$html .=
			'<h3>' . $data["jobInfo"]["name"] .'</h3>'
			. '<p><em>Submitted by '
			. html_tag( "a", array( "href" => swarmpath( "user/{$data["jobInfo"]["ownerName"]}" ) ), $data["jobInfo"]["ownerName"] )
			. ' on ' . htmlspecialchars( date( "Y-m-d H:i:s", gmstrtotime( $data["jobInfo"]["creationTimestamp"] ) ) )
			. ' (UTC)' . '</em>.</p>';

		if ( $request->getSessionData( "auth" ) === "yes" && $data["jobInfo"]["ownerName"] == $request->getSessionData( "username" ) ) {
			$html .= '<script>SWARM.jobInfo = ' . json_encode( $data["jobInfo"] ) . ';</script>'
				. '<div class="form-actions">'
				. ' <button id="swarm-job-delete" class="btn btn-danger">Delete job</button>'
				. ' <button id="swarm-job-reset" class="btn btn-info">Reset job</button>'
				. '</div>'
				. '<div class="alert alert-error" id="swarm-wipejob-error" style="display: none;"></div>';
		}

		$html .= '<table class="table table-bordered swarm-results"><thead><tr><th>&nbsp;</th>';

		// Header with user agents
		foreach ( $data["userAgents"] as $userAgent ) {
			$html .= '<th><img src="' . swarmpath( "images/" . $userAgent["engine"] )
				. '.sm.png" class="swarm-browsericon ' . $userAgent["engine"]
				. '" alt="' . $userAgent["name"]
				. '" title="' . $userAgent["name"]
				. '"><br>'
				. preg_replace( "/\w+ /", "", $userAgent["name"] )
				. '</th>';
		}

		$html .= '</tr></thead><tbody>';

		foreach ( $data["runs"] as $run ) {
			$html .= '<tr><th><a href="' . htmlspecialchars( $run["info"]["url"] ) . '">'
				. $run["info"]["name"] . '</a></th>';

			// Looping over $data["userAgents"] instead of $run["uaRuns"],
			// to avoid shifts in the table (github.com/jquery/testswarm/issues/13)
			foreach ( $data["userAgents"] as $uaID => $uaInfo ) {
				if ( isset( $run["uaRuns"][$uaID] ) ) {
					$uaRun = $run["uaRuns"][$uaID];
					$html .= html_tag_open( "td", array(
						"class" => "status-" . $uaRun["runStatus"],
						"data-job-id" => $data["jobInfo"]["id"],
						"data-run-id" => $run["info"]["id"],
						"data-run-status" => $uaRun["runStatus"],
						"data-useragent-id" => $uaID,
						// Un-ran tests don't have a client id
						"data-client-id" => isset( $uaRun["clientID"] ) ? $uaRun["clientID"] : "",
					));
					if ( isset( $uaRun["runResultsUrl"] ) ) {
						$html .= html_tag( 'a', array(
							"href" => $uaRun["runResultsUrl"],
						), $uaRun["runResultsLabel"] );
					}
					$html .= '</td>';
				} else {
					// This run isn't schedules to be ran in this UA
					$html .= '<td class="notscheduled"></td>';
				}
			}
		}

		$html .= '</tbody></table>';
		return $html;
	}
}
