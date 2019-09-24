import React, {Component, createRef} from 'react'
import {PropTypes as T} from 'prop-types'
import classes from 'classnames'
import moment from 'moment'
import times from 'lodash/times'

import {withRouter} from '#/main/app/router'
import {LinkButton} from '#/main/app/buttons/link'
import {now, getApiFormat} from '#/main/app/intl/date'

import {Event as EventTypes} from '#/plugin/agenda/event/prop-types'
import {EventMicro} from '#/plugin/agenda/event/components/micro'
import {route} from '#/plugin/agenda/tools/agenda/routing'

const Day = props => {
  let dayDiv = createRef()

  return (
    <div
      ref={dayDiv}
      className={classes('calendar-cell day', props.className)}
      onClick={(e) => {
        if (e.target !== dayDiv.current) {
          // avoid event when clicking on day number or an event card
          return
        }

        props.create({
          start: props.current.format(getApiFormat())
        })
      }}
    >
      <LinkButton
        className="day-number"
        target={route(props.path, 'day', props.current)}
      >
        {props.current.format('D')}
      </LinkButton>

      {props.events.map(event => (
        <EventMicro
          key={event.id}
          event={event}
          actions={props.eventActions(event)}
        />
      ))}
    </div>
  )
}

Day.propTypes = {
  path: T.string.isRequired,
  className: T.string,
  current: T.object.isRequired,
  events: T.arrayOf(T.shape(
    EventTypes.propTypes
  )),
  create: T.func.isRequired,
  eventActions: T.func.isRequired
}

Day.defaultProps = {
  events: []
}

class AgendaViewMonthComponent extends Component {
  constructor(props) {
    super(props)

    this.state = {
      scrolled: false
    }

    this.onWheel = this.onWheel.bind(this)
  }

  componentDidUpdate(prevProps) {
    if (this.state.scrolled && this.props.loaded && this.props.loaded !== prevProps.loaded) {
      this.setState({scrolled: false})
    }
  }

  onWheel(e) {
    const deltaY = e.deltaY
    if (!this.state.scrolled) {
      this.setState({scrolled: true})
      if (0 < deltaY) {
        this.props.history.push(
          route(this.props.path, this.props.view, this.props.next(this.props.referenceDate))
        )
      } else {
        this.props.history.push(
          route(this.props.path, this.props.view, this.props.previous(this.props.referenceDate))
        )
      }
    }
  }

  render() {
    const nowDate = moment(now())

    const monthStart = moment(this.props.range[0])
    const monthEnd = moment(this.props.range[1])

    return (
      <div className="agenda-month" onWheel={this.onWheel}>
        <div className="calendar-row day-names">
          {times(7, (dayNum) =>
            <div key={`day-${dayNum}`} className="calendar-cell day-name">
              {moment().weekday(dayNum).format('ddd')}
            </div>
          )}
        </div>

        {times(monthEnd.get('week') - monthStart.get('week') + 1, (weekNum) =>
          <div key={`week-${weekNum}`} className="calendar-row week">
            {times(7, (dayNum) => {
              const current = moment(this.props.range[0])
                .week(this.props.range[0].week()+weekNum)
                .weekday(dayNum)

              // get events for the current day
              const events = this.props.events.filter((event) => {
                const start = moment(event.start)
                const end = moment(event.end)

                return start.isSameOrBefore(current, 'day') && end.isSameOrAfter(current, 'day')
              })

              return (
                <Day
                  key={`day-${weekNum}-${dayNum}`}
                  path={this.props.path}
                  className={classes({
                    now:      current.isSame(nowDate, 'day'),
                    selected: current.isSame(this.props.referenceDate, 'day'),
                    fill:     this.props.range[0].get('month') !== current.get('month')
                  })}
                  current={current}
                  events={events}
                  create={this.props.create}
                  eventActions={this.props.eventActions}
                />
              )
            })}
          </div>
        )}
      </div>
    )
  }
}

AgendaViewMonthComponent.propTypes = {
  history: T.shape({
    push: T.func.isRequired
  }),
  path: T.string.isRequired,
  loaded: T.bool.isRequired,
  view: T.string.isRequired,
  referenceDate: T.object,
  range: T.arrayOf(T.object),
  previous: T.func.isRequired,
  next: T.func.isRequired,

  events: T.arrayOf(T.shape(
    EventTypes.propTypes
  )).isRequired,
  create: T.func.isRequired,
  eventActions: T.func.isRequired
}

const AgendaViewMonth = withRouter(AgendaViewMonthComponent)

export {
  AgendaViewMonth
}
