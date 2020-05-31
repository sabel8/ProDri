# ProDri

*Startup project which taught me PHP, HTML, CSS, JS, MySQL back when I was around 18. Not working on it anymore, left the code open for recruiters.*

The application's main aim is to help organizing the processes in a firm.
ProDri stands for Process Driven, pointing to that processes are controlled by the software.

## The roles:

Every role has it's own unique page, with it's own functions.

 * Regular User (Employee)
   * Process modification recommendation
   * Calendar, creating and managing avalibity.
   * Suggest estimated wprking hours for a certain task in a uninstantiated process.
 * Process Owner (PO)
   * Can assign profession and RACI to a certain task.
   * Can manage abstract process groups, recommendations.
   * All of user's functions.
 * Project Manager (PM)
   * Can create a new project and process
   * Can see the status of the processes and projects.
   * Set delay buffer for task and process.
   * Can estimate the ending of a process.
   * All of user's functions.
 * Line Manager (LM)
   * Creates and modifies the list of employees.
   * All of user's functions.

## The features include:
 * Managing processes inside projects.
 * Visual representation of a process with a native flow-diagram editor.
 * Ability of users to make recommendations for the process.
   * If the change is accepted by the PO, then the next instatiation of the process will be the updated one.
 * Logging changes of related processes to user.
 * Schedule and time management.
   * The users can report their individual avalibility hours.
   * The app has a built in calendar, thus making overviewing even easier.
   * The app can predict the end date of a process that has not been instatiated yet!
 * Files can be uploaded as output(s) of a certain task.
   * These can be refused, if the successor employee is not satisfied
