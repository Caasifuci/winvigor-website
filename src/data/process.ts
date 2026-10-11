import type { Step } from '@/components/blocks/ProcessSteps.astro';

/** How a consulting engagement runs. Each step names its tangible output. */
export const consultingProcess: Step[] = [
  {
    title: 'Assess',
    body: 'We inventory the systems, contracts and teams the programme touches, and score each against business criticality and technical condition.',
    output: 'A scored inventory and an assessment report.',
  },
  {
    title: 'Define',
    body: 'We set a target architecture and sequence the change around your governance, budget cycles and contract dates.',
    output: 'A roadmap your governance board can approve.',
  },
  {
    title: 'Staff',
    body: 'We assemble the delivery team from our consultants and engineers, embedded in your own teams rather than alongside them.',
    output: 'A team with agreed roles and responsibilities.',
  },
  {
    title: 'Deliver',
    body: 'Change ships in planned release windows, with parallel running and rehearsed rollback wherever a service is live.',
    output: 'Working releases, reported against agreed outcomes.',
  },
  {
    title: 'Transfer',
    body: 'Your people take over running and changing what was built. We measure results against the targets set at the start.',
    output: 'Runbooks, documentation and a results review.',
  },
];

/** How a resourcing request runs, from brief to support. */
export const resourcingProcess: Step[] = [
  {
    title: 'Brief',
    body: 'You describe the role, the stack and the team the engineer will join.',
    output: 'An agreed skills profile.',
  },
  {
    title: 'Shortlist',
    body: 'We search the talent board and our wider network. A practitioner assesses every candidate technically before you see them.',
    output: 'A shortlist of assessed profiles.',
  },
  {
    title: 'Interview',
    body: 'You meet the candidates you choose. We handle scheduling and collect feedback.',
    output: 'Your selected engineer.',
  },
  {
    title: 'Onboard',
    body: 'The engineer adopts your standards, tooling and definition of done, and takes direction from your delivery leads.',
    output: 'An engineer working inside your process from week one.',
  },
  {
    title: 'Support',
    body: 'We stay in contact with the engineer and the delivery lead for the length of the assignment.',
    output: 'One point of contact and regular check-ins.',
  },
];

/** How Winvigor hires, for candidates. */
export const hiringProcess: Step[] = [
  {
    title: 'Apply',
    body: 'Send your CV for an open role, or join the talent network if nothing fits yet.',
    output: 'A reply from a person, not an automated filter.',
  },
  {
    title: 'Conversation',
    body: 'A first call about your experience, what you want next and how you prefer to work.',
  },
  {
    title: 'Technical assessment',
    body: 'A practitioner in your discipline reviews your work with you. No trick questions.',
  },
  {
    title: 'Offer',
    body: 'A permanent role or a contract assignment, with the client, team and scope described up front.',
  },
];
