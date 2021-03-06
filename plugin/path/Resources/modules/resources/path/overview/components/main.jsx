import React from 'react'
import {PropTypes as T} from 'prop-types'
import get from 'lodash/get'

import {trans} from '#/main/app/intl/translation'
import {LINK_BUTTON} from '#/main/app/buttons'

import {ResourceOverview} from '#/main/core/resource/components/overview'
import {UserEvaluation as UserEvaluationTypes} from '#/main/core/resource/prop-types'

import {OverviewSummary} from '#/plugin/path/resources/path/overview/components/summary'
import {Path as PathTypes} from '#/plugin/path/resources/path/prop-types'

const OverviewMain = props => {
  let score = {
    displayed: true,
    current: get(props.evaluation, 'progression', 0),
    total: get(props.evaluation, 'progressionMax', get(props.path, 'steps', []).length)
  }

  if (get(props.path, 'display.showScore')) {
    score = {
      displayed: true,
      current: get(props.evaluation, 'score', 0),
      total: get(props.evaluation, 'scoreMax', get(props.path, 'score.total', 0))
    }
  }

  return (
    <ResourceOverview
      contentText={get(props.path, 'meta.description')}
      progression={{
        status: props.evaluation ? props.evaluation.status : undefined,
        score: score
      }}
      actions={[
        { // TODO : implement continue and restart
          type: LINK_BUTTON,
          icon: 'fa fa-fw fa-play icon-with-text-right',
          label: trans('start_path', {}, 'path'),
          target: `${props.basePath}/play`,
          primary: true,
          disabled: props.empty,
          disabledMessages: props.empty ? [trans('start_disabled_empty', {}, 'path')]:[]
        }
      ]}
    >
      <section className="resource-parameters">
        <h3 className="h2">{trans('summary')}</h3>

        <OverviewSummary
          basePath={props.basePath}
          resourceId={props.resourceId}
          steps={props.path.steps}
        />
      </section>
    </ResourceOverview>
  )
}

OverviewMain.propTypes = {
  basePath: T.string.isRequired,
  resourceId: T.string.isRequired,
  path: T.shape(
    PathTypes.propTypes
  ).isRequired,
  empty: T.bool.isRequired,
  evaluation: T.shape(
    UserEvaluationTypes.propTypes
  )
}

OverviewMain.defaultProps = {
  empty: true,
  evaluation: {}
}

export {
  OverviewMain
}
