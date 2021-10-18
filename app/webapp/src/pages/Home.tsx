import * as React from 'react';

const Home = () => {

  return <React.Fragment>
    <h1 className={'title'}>Home</h1>
    <div id="StudentHome">
      <div className="eventSummary alldone">No Event(s) Pending</div>

      <h2>Peer Evaluations</h2>
      <h3>Due</h3>
      <table className="standardtable">
        <tbody>
        <tr>
          <th>Event</th>
          <th>Group</th>
          <th>Course</th>
          <th>Due Date</th>
          <th>Due In/<span className="red">Late By</span></th>
        </tr>
        <tr></tr>
        <tr>
          <td colSpan={5} align="center"><b> No peer evaluations due at this time </b></td>
        </tr>
        </tbody>
      </table>

      <h3>Submitted</h3>
      <table className="standardtable">
        <tbody>
        <tr>
          <th>Event</th>
          <th>Result <span className="orangered">Available</span>/End</th>
          <th>Group</th>
          <th>Course</th>
          <th>Due Date</th>
          <th>Date Submitted</th>
        </tr>
        <tr></tr>
        <tr>
          <td colSpan={6} align="center">No submitted evaluations available.</td>
        </tr>
        </tbody>
      </table>

      <h2>Surveys</h2>
      <h3>Due</h3>
      <table className="standardtable">
        <tbody>
        <tr>
          <th>Event</th>
          <th>Course</th>
          <th>Due Date</th>
          <th>Due In/<span className="red">Late By</span></th>
        </tr>
        <tr></tr>
        <tr>
          <td colSpan={4} align="center"><b> No survey due at this time </b></td>
        </tr>
        </tbody>
      </table>

      <h3>Submitted</h3>
      <table className="standardtable">
        <tbody>
        <tr>
          <th>Event</th>
          <th>Course</th>
          <th>Due Date</th>
          <th>Date Submitted</th>
        </tr>
        <tr></tr>
        <tr>
          <td colSpan={4} align="center">No submitted surveys available.</td>
        </tr>
        </tbody>
      </table>

    </div>
  </React.Fragment>
}
export default Home;