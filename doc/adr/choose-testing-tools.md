# Architectural Decision Record (ADR)

## Title: Choosing React Testing Library over Enzyme for Component Testing

### Abstract

The purpose of this document is to outline the rationale behind selecting React Testing Library (RTL) over Enzyme for
testing React components in our software development process. This decision is based on several key factors, including
testing principles, ease of use, and sustainability of testing practices.

### Decision

After a comprehensive evaluation, we have chosen to adopt React Testing Library (RTL) as our primary testing framework
for React components instead of using Enzyme.

### Rationale

#### 1. Philosophy and Principles

- RTL aligns more closely with the principles of testing within the React ecosystem. Its philosophy revolves around
  testing the application from the user's perspective, focusing on behavior-driven testing. It encourages writing tests
  that resemble how users interact with the application, resulting in more resilient and maintainable tests.
- Enzyme, while powerful and flexible, often promotes testing implementation details, leading to brittle tests that are
  tightly coupled to the component structure. This approach can result in frequent test breakages during refactoring,
  impacting development velocity.

#### 2. Simplicity and Accessibility

- RTL provides a simpler and more user-friendly API, making it easier for both experienced and novice developers to
  write tests efficiently. Its straightforward querying methods using "queries" such as getByText, getByRole, etc.,
  reduce the learning curve and enhance readability.
- In contrast, Enzyme's API can be complex, offering multiple ways to access and manipulate component instances, leading
  to varying practices within a team and potentially hindering code comprehension and maintenance.

#### 3. Community Support and Sustainability

- The React Testing Library has gained significant traction within the React community and is endorsed by the official
  React team. It enjoys active maintenance, frequent updates, and a supportive community, ensuring ongoing support and
  evolution.
- Enzyme, although a popular choice in the past, has shown signs of reduced activity and official backing. The
  development has slowed, and it might not keep up with evolving best practices in the React ecosystem.

#### 4. Future-Proofing and Compatibility

- As React evolves, RTL is designed to be more future-proof. It maintains compatibility with new React features and
  changes in the library, reducing the likelihood of the testing framework becoming outdated.
- Enzyme's future compatibility with React's advancements might become a concern, potentially leading to the need for
  migration or causing delays in adopting new React features.

### Action Plan

We will integrate RTL and all the JEST and other needed extensions in the project.

### Conclusion

The decision to adopt React Testing Library over Enzyme aligns with our commitment to robust, user-centric testing
practices, ease of use, and long-term sustainability. This transition will empower our team to write more resilient and
maintainable tests, ensuring a higher quality of our React components.

This decision is effective immediately and will be integrated into our ongoing development and testing workflows.
